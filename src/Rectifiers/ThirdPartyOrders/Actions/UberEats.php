<?php

namespace Weboccult\EatcardCompanion\Rectifiers\ThirdPartyOrders\Actions;

use Carbon\Carbon;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Redis;
use Weboccult\EatcardCompanion\Models\Order;
use Weboccult\EatcardCompanion\Models\OrderItem;
use Weboccult\EatcardCompanion\Models\Product;
use Weboccult\EatcardCompanion\Models\Store;
use Weboccult\EatcardCompanion\Models\StoreUberEatsSetting;
use Weboccult\EatcardCompanion\Models\Supplement;
use Weboccult\EatcardCompanion\Rectifiers\ThirdPartyOrders\ThirdPartyOrders;

/**
 * @author Darshit Hedpara
 */
class UberEats extends ThirdPartyOrders
{
    /** @var StoreUberEatsSetting|null|object */
    protected ?StoreUberEatsSetting $storeUberEatsSetting = null;

    /** @var Order|null|object */
    protected ?Order $existedOrder;

    /** @var string|int */
    protected $resourceId = null;

    /** @var Store|null|object */
    protected ?Store $store;

    /** @var string|null */
    protected ?string $token;

    /** @var array|object */
    protected $response;

    public const CREATED_ACTION = 'CREATED';
    public const ACCEPTED_ACTION = 'ACCEPTED';
    public const FINISHED_ACTION = 'FINISHED';
    public const CANCELED_ACTION = 'CANCELED';
    public const DENIED_ACTION = 'DENIED';

    /**
     * @param $data
     *
     * @return void
     */
    public function handle($data)
    {
        try {
            $this->resourceId = $data['meta']['resource_id'];
            $this->existedOrder = Order::where('uber_eats_order_id', $this->resourceId)->first();
            $this->storeUberEatsSetting = StoreUberEatsSetting::query()
                ->where('restaurant_id', $data['meta']['user_id'])
                ->first();
            $this->store = Store::where('id', $this->storeUberEatsSetting->store_id)->first();
            if ($this->storeUberEatsSetting && $this->storeUberEatsSetting->is_uber_eats == 1) {
                return;
            }
            $this->token = $this->getAccessToken();
            $this->fetchOrder();
        } catch (Exception $e) {
        }
    }

    /**
     * @return string|null
     */
    public function getAccessToken(): ?string
    {
        try {
            $client = new Client();
            /*Generating Access token*/
            $request = $client->post('https://login.uber.com/oauth/v2/token', [
                'form_params' => [
                    'client_id'     => $this->storeUberEatsSetting->client_id,
                    'client_secret' => $this->storeUberEatsSetting->client_secret,
                    'grant_type'    => 'client_credentials',
                    'scope'         => 'eats.store.orders.read',
                ],
            ]);
            $statusCode = $request->getStatusCode();
            $request->getHeaderLine('content-type');
            $this->response = json_decode($request->getBody()->getContents(), true);
            if ($statusCode == 200) {
                return $this->response['access_token'];
            } else {
                return null;
            }
        } catch (GuzzleException $e) {
            return null;
        }
    }

    private function fetchOrder()
    {
        try {
            $client = new Client([
                'headers' => [
                    'Authorization' => 'Bearer '.$this->token,
                    'Content-Type'  => 'application/json',
                ],
            ]);
            $request = $client->request('get', 'https://api.uber.com/v2/eats/order/'.$this->resourceId);
            $this->response = json_decode($request->getBody()->getContents(), true);
            if (isset($this->response['current_state']) && ! empty($this->response['current_state'])) {
                $this->performActions($this->response['current_state']);
            }
        } catch (GuzzleException $e) {
            return;
        }
    }

    /**
     * @param $action
     *
     * @return void
     */
    private function performActions($action)
    {
        switch ($action) {
            case self::CREATED_ACTION:
                $this->createAction();
                break;
            case self::ACCEPTED_ACTION:
                if (! empty($this->existedOrder)) {
                    $this->existedOrder->update(['order_status' => 'preparing']);
                }
                break;
            case self::FINISHED_ACTION:
                if (! empty($this->existedOrder)) {
                    $this->existedOrder->update(['order_status' => 'done']);
                }
                break;
            case self::CANCELED_ACTION:
            case self::DENIED_ACTION:
                if (! empty($this->existedOrder)) {
                    $this->existedOrder->update(['order_status' => 'canceled']);
                }
                break;
            default:
                break;
        }
        if (! empty($this->existedOrder) && in_array($action, [
                self::ACCEPTED_ACTION,
                self::FINISHED_ACTION,
                self::CANCELED_ACTION,
                self::DENIED_ACTION,
            ])) {
            $this->existedOrder->dutch_order_status = __('eatcard-companion::general.'.$this->existedOrder->order_status);
            $redis = Redis::connection();
            $redis->publish('order_status_update', json_encode([
                'id'                   => $this->existedOrder->id,
                'store_id'             => $this->existedOrder->store_id,
                'order_id'             => $this->existedOrder->order_id,
                'status'               => $this->existedOrder->order_status,
                'is_picked_up'         => $this->existedOrder->is_picked_up,
                'dutch_order_status'   => $this->existedOrder->dutch_order_status,
                'method'               => $this->existedOrder->method,
                'payment_status'       => $this->existedOrder->status,
                'dutch_payment_status' => __('messages.'.$this->existedOrder->status),
                'uber_eats_order_id'   => $this->existedOrder->uber_eats_order_id,
                'message'              => __('messages.dine_in_order_notification_message', ['status' => $this->existedOrder->dutch_order_status]),
            ]));
        }
    }

    private function createAction()
    {
        $this->prepareOrderData();
        $this->prepareDeliveryData();
        $this->prepareOrderItemData();
        $this->createOrderAndOrderItems();
        $this->orderAutoAccept();
        $this->createNotification();
        $this->socketPublish();
        $this->sendJsonToSQSOrSetFuturePrint();
    }

    private function prepareOrderData()
    {
        if (isset($this->response['type'])) {
            if ($this->response['type'] == 'PICK_UP') {
                $order_time = isset($this->response['estimated_ready_for_pickup_at']) ? Carbon::parse($this->response['estimated_ready_for_pickup_at'])
                    ->timezone('Europe/Amsterdam')
                    ->format('H:i') : Carbon::now()->timezone('Europe/Amsterdam')->format('H:i');
            }
            if (($this->response['type'] == 'DELIVERY_BY_UBER') || ($this->response['type'] == 'DELIVERY_BY_RESTAURANT')) {
                $order_time = isset($this->response['estimated_ready_for_pickup_at']) ? Carbon::parse($this->response['estimated_ready_for_pickup_at'])
                    ->timezone('Europe/Amsterdam')
                    ->format('H:i') : Carbon::now()->timezone('Europe/Amsterdam')->format('H:i');
            }
        }
        if (empty($order_time)) {
            // add logs here...
            return;
        }
        $orderDate = isset($this->response['placed_at']) && strtotime($this->response['placed_at']) ? Carbon::parse($this->response['placed_at'])
            ->format('Y-m-d') : Carbon::now()->format('Y-m-d');
        $this->orderData = [
            'order_id'              => isset($this->response['display_id']) && $this->response['display_id'] ? $this->response['display_id'] : 0,
            'store_id'              => $this->storeUberEatsSetting->store_id,
            'uber_eats_order_id'    => $this->resourceId,
            'user_id'               => '',
            'alcohol_sub_total'     => 0,
            'normal_sub_total'      => 0,
            'sub_total'             => isset($this->response['payment']['charges']['sub_total']['amount']) ? $this->response['payment']['charges']['sub_total']['amount'] / 100 : 0,
            'discount'              => '',
            'discount_type'         => isset($this->response['promotions']) ? $this->response['promotions']['promo_type'] : '',
            'discount_amount'       => isset($this->response['promotions']['promo_discount_value']) && $this->response['promotions']['promo_discount_value'] ? $this->response['promotions']['promo_discount_value'] : 0,
            'total_tax'             => isset($this->response['payment']['charges']['tax']['amount']) && $this->response['payment']['charges']['tax']['amount'] ? $this->response['payment']['charges']['tax']['amount'] / 100 : 0,
            'total_alcohol_tax'     => 0,
            'delivery_fee'          => isset($this->response['payment']['charges']['total_fee']['amount']) && $this->response['payment']['charges']['total_fee']['amount'] != null ? ($this->response['payment']['charges']['total_fee']['amount']) / 100 : 0,
            'plastic_bag_fee'       => isset($this->response['payment']['charges']['bag_fee']['amount']) && $this->response['payment']['charges']['bag_fee']['amount'] != null ? ($this->response['payment']['charges']['bag_fee']['amount']) / 100 : 0,
            'total_price'           => isset($this->response['payment']['charges']['sub_total']['amount']) && $this->response['payment']['charges']['sub_total']['amount'] ? $this->response['payment']['charges']['sub_total']['amount'] / 100 : 0,
            'original_order_total'  => isset($this->response['payment']['charges']['sub_total']['amount']) && $this->response['payment']['charges']['sub_total']['amount'] ? $this->response['payment']['charges']['sub_total']['amount'] / 100 : 0,
            'order_date'            => $orderDate,
            'order_time'            => $order_time,
            'status'                => 'paid',
            'payment_method_type'   => '',
            'method'                => isset($this->response['paymentMethod']) && $this->response['paymentMethod'] ? $this->response['paymentMethod'] : 'Uber',
            'paid_on'               => Carbon::now()->format('Y-m-d H:i:s'),
            'order_type'            => isset($this->response['type']) && $this->response['type'] ? ($this->response['type'] == 'PICK_UP' ? 'pickup' : (($this->response['type'] == 'DELIVERY_BY_UBER' || $this->response['type'] == 'DELIVERY_BY_RESTAURANT') ? 'delivery' : '')) : '',
            'order_status'          => 'received',
            'comment'               => isset($this->response['cart']['special_instructions']) && $this->response['cart']['special_instructions'] ? $this->response['cart']['special_instructions'] : '',
            'created_from'          => 'admin',
            'table_name'            => '',
            'is_takeaway_mail_send' => 0,
        ];
    }

    private function prepareDeliveryData()
    {
        if (isset($this->response['eater']) && $this->response['eater']) {
            if (isset($this->response['eater']['delivery'])) {
                $place_id = $this->response['eater']['delivery']['location']['google_place_id'];
                $deliveryData = $this->getDeliveryDetails($place_id);
                if ($deliveryData) {
                    $route = '';
                    $locality = '';
                    $country = '';
                    $street = '';
                    foreach ($deliveryData['result']['address_components'] as $res) {
                        if (in_array('postal_code', $res['types'])) {
                            $this->orderData['delivery_postcode'] = $res['long_name'] ?? '';
                        }
                        if (in_array('locality', $res['types'])) {
                            $locality = $res['long_name'];
                            $this->orderData['delivery_place'] = $res['long_name'] ?? '';
                        }
                        if (in_array('route', $res['types'])) {
                            $route = $res['long_name'];
                        }
                        if (in_array('street_number', $res['types'])) {
                            $street = $res['long_name'];
                        }
                        if (in_array('country', $res['types'])) {
                            $country = $res['long_name'];
                        }
                    }
                    $address = $route.' '.$street.', '.$locality.', '.$country;
                    $this->orderData['delivery_address'] = $address;
                    $this->orderData['delivery_latitude'] = $deliveryData['result']['geometry']['location']['lat'];
                    $this->orderData['delivery_longitude'] = $deliveryData['result']['geometry']['location']['lng'];
                }
            }
            $this->orderData['first_name'] = $this->response['eater']['first_name'] ?? '';
            $this->orderData['last_name'] = $this->response['eater']['last_name'] ?? '';
            $this->orderData['contact_no'] = $this->response['eater']['phone_code'] ?? '';
        }
    }

    private function prepareOrderItemData()
    {
        $this->orderItemData = [];
        if (isset($this->response['cart']['items']) && count($this->response['cart']['items']) > 0) {
            foreach ($this->response['cart']['items'] as $pro_key => $product) {
                $supplement_total = 0;
                $supplements = [];
                /*Supplement Array*/
                if (isset($product['selected_modifier_groups']) && $product['selected_modifier_groups']) {
                    foreach ($product['selected_modifier_groups'] as $dish_key => $sideDish) {
                        foreach ($sideDish['selected_items'] as $extra) {
                            $supplement_total += isset($extra['price']['unit_price']['amount']) ? $extra['price']['unit_price']['amount'] / 100 : 0;
                            $supplements[$dish_key] = [
                                'id'                          => isset($extra['id']) && $extra['id'] ? $extra['id'] : '',
                                'external_data_supplement_id' => isset($extra['external_data']) && $extra['external_data'] ? $extra['external_data'] : '',
                                'name'                        => isset($extra['title']) && $extra['title'] ? $extra['title'] : '',
                                'qty'                         => isset($extra['quantity']) && $extra['quantity'] ? $extra['quantity'] : '',
                                'val'                         => isset($extra['price']['unit_price']['amount']) ? $extra['price']['unit_price']['amount'] / 100 : '',
                            ];
                        }
                    }
                }
                $product_data = null;
                if (isset($product['external_data'])) {
                    $product_data = Product::with('category')
                        ->where('store_id', $this->store->id)
                        ->where('uber_eats_id', $product['external_data'])
                        ->first();
                }
                if (is_null($product_data)) {
                    $product_data = Product::with('category')
                        ->where('store_id', $this->store->id)
                        ->where('uber_eats_id', $product['id'])
                        ->first();
                }
                $product_tax = isset($product_data->tax) && $product_data->tax != null ? $product_data->tax : (isset($product_data->category) ? $product_data->category->tax : 0);
                $total_price = (isset($product['price']['unit_price']['amount']) && $product['price']['unit_price']['amount'] ? $product['price']['unit_price']['amount'] / 100 : 0) + $supplement_total;
                $this->orderItemData[$pro_key] = [
                    'product_id'               => isset($product['id']) && $product['id'] ? $product['id'] : '',
                    'external_data_product_id' => isset($product['external_data']) && $product['external_data'] ? $product['external_data'] : '',
                    'product_name'             => isset($product['title']) && $product['title'] ? $product['title'] : '',
                    'quantity'                 => isset($product['quantity']) && $product['quantity'] ? $product['quantity'] : '',
                    'unit_price'               => $total_price - $supplement_total,
                    'total_price'              => $total_price,
                    'tax_percentage'           => $product_tax,
                    'subtotal_inc_tax'         => $total_price,
                    'normal_tax_amount'        => $product_tax == 9 ? ($total_price * 9) / 109 : 0,
                    'alcohol_tax_amount'       => $product_tax == 21 ? ($total_price * 21) / 121 : 0,
                    'supplement_total'         => $supplement_total,
                    'total_tax_amount'         => $product_tax == 21 ? ($total_price * 21) / 121 : ($total_price * 9) / 109,
                    'subtotal_wo_tax'          => $total_price - (($total_price * $product_tax) / (100 + $product_tax)),
                    'comment'                  => $product['special_instructions'] ?? '',
                ];
                $sup_items = [];
                if (isset($supplements) && count($supplements) > 0) {
                    foreach ($supplements as $supplement) {
                        $sup = Supplement::where('store_id', $this->store->id)
                            ->where('uber_eats_id', $supplement['external_data_supplement_id'])
                            ->first();
                        if (is_null($sup)) {
                            $sup = Supplement::where('store_id', $this->store->id)
                                ->where('uber_eats_id', $supplement['id'])
                                ->first();
                        }
                        if ($sup) {
                            $supplement['id'] = $sup->id;
                            $supplement['categoryId'] = null;
                            $supplement['alt_name'] = $sup->alt_name ?? null;
                            $sup_items[] = $supplement;
                        }
                    }
                }
                $this->orderItemData['extra'] = json_encode([
                    'serve_type'  => [],
                    'size'        => [],
                    'supplements' => $sup_items,
                    'users'       => [],
                ]);
            }
        }
        $this->orderData['items'] = $this->orderItemData;
    }

    private function createOrderAndOrderItems()
    {
        $this->createdOrder = Order::query()->create($this->orderData);
        foreach ($this->orderItemData as $key => $item) {
            $this->orderItemData[$key]['order_id'] = $this->createdOrder->id;
        }
        OrderItem::query()->insert($this->orderItemData);
    }

    /**
     * @param $placeId
     *
     * @return mixed|null
     */
    private function getDeliveryDetails($placeId)
    {
        try {
            $client = new Client();
            $request = $client->request('get', 'https://maps.googleapis.com/maps/api/place/details/json?place_id='.$placeId.'&fields=address_components,formatted_address,geometry&key='.env('GOOGLE_MAP_API_KEY_OTHERS'));
            $status = $request->getStatusCode();
            if ($status == 200) {
                return json_decode($request->getBody()->getContents(), true);
            } else {
                return null;
            }
        } catch (GuzzleException $e) {
            return null;
        }
    }

    private function orderAutoAccept()
    {
        try {
            $client = new Client([
                'headers' => [
                    'Authorization' => 'Bearer '.$this->token,
                    'Content-Type'  => 'application/json',
                ],
            ]);
            $client->post('https://api.uber.com/v1/eats/orders/'.$this->resourceId.'/accept_pos_order', [
                'body' => json_encode([
                    'reason'      => 'accepted',
                    'pickup_time' => 0,
                ]),
            ]);
            // order accept success
        } catch (GuzzleException $e) {
            // order accept fail
        }
    }

    private function socketPublish()
    {
        $redis = Redis::connection();
        $redis->publish('new_order', json_encode([
            'store_id'        => $this->store->id,
            'notification_id' => $this->createdNotification->id,
            'additional_data' => $this->createdNotification->additional_data,
        ]));
    }
}
