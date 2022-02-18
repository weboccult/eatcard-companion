<?php

namespace Weboccult\EatcardCompanion\Rectifiers\ThirdPartyOrders\Actions;

use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Weboccult\EatcardCompanion\Models\Order;
use Weboccult\EatcardCompanion\Models\OrderItem;
use Weboccult\EatcardCompanion\Models\Product;
use Weboccult\EatcardCompanion\Models\Store;
use Weboccult\EatcardCompanion\Models\Supplement;
use Weboccult\EatcardCompanion\Rectifiers\ThirdPartyOrders\ThirdPartyOrders;

/**
 * @author Darshit Hedpara
 */
class Deliveroo extends ThirdPartyOrders
{
    private $API_URL;
    private $CREDENTIAL;

    /** @var string|int */
    protected $locationId = null;

    /** @var array */
    protected array $payloadData = [];

    public const CREATED_EVENT = 'new_order';
    public const CANCELED_EVENT = 'cancel_order';

    public function __construct()
    {
        $this->API_URL = config('eatcardCompanion.third_party.deliveroo.url');
        $this->CREDENTIAL = config('eatcardCompanion.third_party.deliveroo.credential');
    }

    public function handle(array $data)
    {
        $this->payloadData = $data;
        $this->locationId = $data['location_id'];
        $this->store = Store::query()->where('id', $this->locationId)->first();
        $this->performActions($this->payloadData['event']);
    }

    /**
     * @param $action
     *
     * @return void
     */
    private function performActions($action)
    {
        switch ($action) {
            case self::CREATED_EVENT:
                $this->createEvent();
                break;
            case self::CANCELED_EVENT:
                if (! empty($this->existedOrder)) {
                    // $this->existedOrder->update(['order_status' => 'cancel_order']);
                }
                break;
            default:
                break;
        }
    }

    private function createEvent()
    {
        if (! empty($this->store) && $this->store->is_check_deliveroo == 1) {
            return;
        }
        $this->prepareOrderData();
        $this->prepareASAPData();
        $this->prepareDeliveryData();
        $this->prepareOrderItemData();
        $this->createOrderAndOrderItems();
        $this->createNotification();
        $this->sendJsonToSQSOrSetFuturePrint();
        $this->updateOrderSyncStatus('succeeded', $this->createdOrder->deliveroo_order_id);
    }

    private function prepareOrderData()
    {
        $this->orderData = [
            'order_id'              => (int) $this->payloadData['order']['display_id'] ?? 0,
            'store_id'              => $this->store->id,
            'deliveroo_order_id'    => $this->payloadData['order']['id'],
            'user_id'               => '',
            'alcohol_sub_total'     => 0,
            'normal_sub_total'      => 0,
            'sub_total'             => isset($this->payloadData['order']['total_price']['fractional']) ? (int) $this->payloadData['order']['total_price']['fractional'] / 100 : 0,
            'discount'              => '',
            'discount_type'         => '',
            'discount_amount'       => 0,
            'total_tax'             => 0,
            'total_alcohol_tax'     => 0,
            'delivery_fee'          => 0,
            'plastic_bag_fee'       => 0,
            'total_price'           => isset($this->payloadData['order']['total_price']['fractional']) ? (int) $this->payloadData['order']['total_price']['fractional'] / 100 : 0,
            'order_date'            => '',
            'order_time'            => '',
            'status'                => 'paid',
            'payment_method_type'   => '',
            'method'                => 'deliveroo',
            'paid_on'               => Carbon::now()->format('Y-m-d H:i:s'),
            'order_type'            => isset($this->payloadData['order']['delivery']) && $this->payloadData['order']['delivery'] ? 'delivery' : 'pickup',
            'order_status'          => 'received',
            'comment'               => $this->payloadData['order']['notes'] ?? '',
            'created_from'          => 'admin',
            'is_asap'               => isset($this->payloadData['order']['asap']) && $this->payloadData['order']['asap'] === true ? 1 : 0,
            'table_name'            => '',
            'is_takeaway_mail_send' => 0,
        ];
    }

    private function prepareASAPData()
    {
        if (isset($this->payloadData['order']['asap'])) {
            if (! empty($this->payloadData['order']['fulfillment_type'] ?? null) && $this->payloadData['order']['fulfillment_type'] == 'deliveroo') {
                $this->orderData['order_date'] = Carbon::parse($this->payloadData['order']['pickup_at'])
                    ->format('Y-m-d');
                $this->orderData['order_time'] = Carbon::parse($this->payloadData['order']['pickup_at'])->format('H:i');
            }
            if (! empty($this->payloadData['order']['fulfillment_type'] ?? null) && $this->payloadData['order']['fulfillment_type'] == 'restaurant') {
                $this->orderData['order_date'] = Carbon::parse($this->payloadData['order']['delivery']['deliver_by'])
                    ->format('Y-m-d');
                $this->orderData['order_time'] = Carbon::parse($this->payloadData['order']['delivery']['deliver_by'])
                    ->format('H:i');
            }
        }
    }

    private function prepareDeliveryData()
    {
        if (isset($this->payloadData['order']['delivery']) && ! empty($this->payloadData['order']['delivery'])) {
            $this->orderData['delivery_latitude'] = $this->payloadData['order']['delivery']['location']['latitude'] ?? null;
            $this->orderData['delivery_longitude'] = $this->payloadData['order']['delivery']['location']['longitude'] ?? null;
            $this->orderData['contact_no'] = $this->payloadData['order']['delivery']['contact_number'] ?? null;
            $this->orderData['delivery_postcode'] = $this->payloadData['order']['delivery']['postcode'] ?? null;
            $this->orderData['first_name'] = $this->payloadData['order']['delivery']['customer_name'] ?? '';
            $line1 = $this->payloadData['order']['delivery']['line1'] ?? '';
            $line2 = $this->payloadData['order']['delivery']['line2'] ?? '';
            $city = $this->payloadData['order']['delivery']['city'] ?? '';
            $this->orderData['delivery_address'] = $line1.' '.$line2.','.$city;
            // TODO ask for type restaurant
            // if (isset($this->payloadData['order']['delivery']['delivery_fee']) && !empty ($this->payloadData['order']['delivery']['delivery_fee'])) {
            //$this->orderData['delivery_fee'] = $this->payloadData['order']['delivery']['delivery_fee']['fractional'] / 100;
            // }
        }
    }

    private function prepareOrderItemData()
    {
        if (isset($this->payloadData['order']['items']) && count($this->payloadData['order']['items']) > 0) {
            $orderItemsData = [];
            foreach ($this->payloadData['order']['items'] as $pro_key => $product) {
                $supplement_total = 0;
                $supplements = [];
                if (isset($product['modifiers']) && ! empty($product['modifiers'])) {
                    foreach ($product['modifiers'] as $dish_key => $sideDish) {
                        $supplement_total += (($sideDish['quantity']) * (isset($sideDish['unit_price']['fractional']) ? (int) $sideDish['unit_price']['fractional'] / 100 : 0));
                        $supplements[] = [
                            'id'   => $sideDish['pos_item_id'] ?? '',
                            'name' => isset($sideDish['pos_item_id']) && ! empty($sideDish['pos_item_id']) ? Supplement::query()
                                ->where('deliveroo_id', $sideDish['pos_item_id'])
                                ->value('name') : '',
                            'qty'  => $sideDish['quantity'] ?? '',
                            'val'  => isset($sideDish['unit_price']['fractional']) && $sideDish['unit_price']['fractional'] ? $sideDish['unit_price']['fractional'] / 100 : 0,
                        ];
                    }
                }
                $product_data = Product::query()
                    ->with('category')
                    ->where('deliveroo_id', $product['pos_item_id'])
                    ->first();
                $product_tax = isset($product_data->tax) && $product_data->tax != null ? $product_data->tax : (isset($product_data->category) ? $product_data->category->tax : 0);
                $unit_price = isset($product['unit_price']['fractional']) ? (int) $product['unit_price']['fractional'] / 100 : 0;
                $total_price = ($unit_price + $supplement_total) * (isset($product['quantity']) ? (int) $product['quantity'] : 1);
                /*<--- Order item array --->*/
                $orderItemsData[$pro_key] = [
                    'product_id'         => $product_data->id ?? $product['pos_item_id'] ?? '',
                    'product_name'       => $product_data->name ?? '',
                    'quantity'           => $product['quantity'] ?? '',
                    'unit_price'         => $unit_price,
                    'total_price'        => $total_price,
                    'tax_percentage'     => $product_tax,
                    'subtotal_inc_tax'   => $total_price,
                    'normal_tax_amount'  => $product_tax == 9 ? ($total_price * 9) / 109 : 0,
                    'alcohol_tax_amount' => $product_tax == 21 ? ($total_price * 21) / 121 : 0,
                    'supplement_total'   => $supplement_total,
                    'total_tax_amount'   => $product_tax == 21 ? (($total_price * 21) / 121) : (($total_price * 9) / 109),
                    'subtotal_wo_tax'    => $total_price - (($total_price * $product_tax) / (100 + $product_tax)),
                    'comment'            => '',
                ];
                $finalSupplements = [];
                if (count($supplements) > 0) {
                    foreach ($supplements as $supplement) {
                        $sup = Supplement::query()
                            ->where('store_id', $this->store->id)
                            ->where('deliveroo_id', $supplement['id'])
                            ->first();
                        if ($sup) {
                            $supplement['id'] = $sup->id;
                            $supplement['categoryId'] = null;
                            $supplement['alt_name'] = $sup->alt_name ?? null;
                            $finalSupplements[] = $supplement;
                        }
                    }
                }
                $orderItemsData['extra'] = json_encode([
                    'serve_type'  => [],
                    'size'        => [],
                    'supplements' => $finalSupplements,
                    'users'       => [],
                ]);
                $orderItemsData[$pro_key]['supplements'] = $supplements;
            }
            $this->orderItemData = $orderItemsData;
        }
    }

    private function createOrderAndOrderItems()
    {
        try {
            $this->createdOrder = Order::create($this->orderData);
            foreach ($this->orderItemData as $key => $item) {
                $item['order_id'] = $this->createdOrder->id;
                OrderItem::create($item);
            }
        } catch (\Exception $e) {
        }
    }

    /**
     * @description update deliveroo order sync status
     *
     * @param $sync_status : string
     * @param $order_id
     *
     * @return mixed
     */
    public function updateOrderSyncStatus($sync_status, $order_id)
    {
        try {
            if (empty($this->API_URL ?? null) && empty($this->CREDENTIAL ?? null)) {
                return response()->json([
                    'status'  => 'fail',
                    'message' => 'CREDENTIAL not set',
                    'code'    => 400,
                ], 200);
            }
            $credentials = base64_encode($this->CREDENTIAL);
            $client = new Client([
                'headers' => [
                    'Authorization' => 'Basic '.$credentials,
                    'Content-Type'  => 'application/json;charset=UTF-8',
                ],
            ]);
            $json_data = json_encode([
                'occurred_at' => Carbon::now('UTC'),
                'status'      => $sync_status,
            ]);
            $request = $client->request('POST', $this->API_URL.'/v1/orders/'.$order_id.'/sync_status', [
                'body' => $json_data,
            ]);
            $request = json_decode($request->getBody()->getContents(), true);

            return response()->json([
                'status' => 'success',
                'data'   => $request,
                'code'   => 200,
            ], 200);
        } catch (GuzzleException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
                'code'    => 400,
            ], 400);
        }
    }
}
