<?php

namespace Weboccult\EatcardCompanion\Rectifiers\ThirdPartyOrders\Actions;

use Carbon\Carbon;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Weboccult\EatcardCompanion\Models\Notification;
use Weboccult\EatcardCompanion\Models\Order;
use Weboccult\EatcardCompanion\Models\OrderHistory;
use Weboccult\EatcardCompanion\Models\OrderItem;
use Weboccult\EatcardCompanion\Models\Product;
use Weboccult\EatcardCompanion\Models\Store;
use Weboccult\EatcardCompanion\Models\Supplement;
use Weboccult\EatcardCompanion\Rectifiers\ThirdPartyOrders\ThirdPartyOrders;
use function Weboccult\EatcardCompanion\Helpers\appDutchDate;

class Thusibezorgd extends ThirdPartyOrders
{
    /** @var array<Store>|null */
    protected $stores;

    /** @var array<array> */
    protected $orderResponseStoreWise = [];

    /** @var array<array> */
    protected $orderDataStoreWise = [];

    public function handle(array $data)
    {
        $stores = Store::whereHas('StoreThuisbezorgd', function ($q1) {
            $q1->where('is_thuisbezorgd', 1)
                ->whereNotNull('user_name')
                ->whereNotNull('password')
                ->whereNotNull('api_key')
                ->whereNotNull('res_id');
        })->with([
            'sqs',
            'StoreThuisbezorgd' => function ($q1) {
                $q1->where('is_thuisbezorgd', 1);
            },
        ])->get();
        $this->fetchOrders();
        $this->prepareOrderDataStoreWise();
        $this->saveOrdersIntoDatabase();
    }

    private function fetchOrders()
    {
        foreach ($this->stores as $store) {
            try {
                $client = new Client([
                    'auth'    => [
                        $store->StoreThuisbezorgd->user_name,
                        $store->StoreThuisbezorgd->password,
                    ],
                    'headers' => ['Apikey' => $store->StoreThuisbezorgd->api_key],
                ]);
                $request = $client->request('GET', 'https://posapi.takeaway.com/1.0/orders/'.$store->StoreThuisbezorgd->res_id);
                $statusCode = $request->getStatusCode();
                $request->getHeaderLine('content-type');
                $response = json_decode($request->getBody()->getContents(), true);
                if ($statusCode == 200) {
                    $this->orderResponseStoreWise[$store->id] = $response['orders'];
                }
            } catch (GuzzleException $e) {
            }
        }
    }

    private function prepareOrderDataStoreWise()
    {
        foreach ($this->orderResponseStoreWise as $storeId => $orders) {
            foreach ($orders as $order) {
                $this->orderDataStoreWise[$storeId] = [];
                try {
                    $order_time = Carbon::now()->format('H:i');
                    if (isset($order['orderType']) && $order['orderType']) {
                        if ($order['orderType'] == 'pickup' && isset($order['requestedPickupTime']) && strtotime($order['requestedPickupTime'])) {
                            $order_time = Carbon::parse($order['requestedPickupTime'])
                                ->timezone('Europe/Amsterdam')
                                ->format('H:i');
                        }
                        if ($order['orderType'] == 'delivery' && isset($order['requestedDeliveryTime']) && strtotime($order['requestedDeliveryTime'])) {
                            $order_time = Carbon::parse($order['requestedDeliveryTime'])
                                ->timezone('Europe/Amsterdam')
                                ->format('H:i');
                        }
                    }
                    $orderDate = Carbon::now()->format('Y-m-d');
                    try {
                        if (isset($order['orderDate']) && strtotime($order['orderDate'])) {
                            $orderDate = Carbon::parse($order['orderDate'])
                                ->timezone('Europe/Amsterdam')
                                ->format('Y-m-d');
                        }
                    } catch (Exception $oDate) {
                    }
                    $orderData = [
                        'is_asap'               => (isset($order['requestedPickupTime']) && $order['requestedPickupTime'] == 'ASAP') ? 1 : 0,
                        'order_id'              => $order['publicReference'] ?? 0,
                        'store_id'              => $storeId,
                        'thusibezorgd_order_id' => $order['id'] ?? 0,
                        'thusibezorgd_res_id'   => $order['restaurantId'] ?? 0,
                        'user_id'               => '',
                        'alcohol_sub_total'     => 0,
                        'normal_sub_total'      => 0,
                        'sub_total'             => 0,
                        'discount'              => '',
                        'discount_type'         => '',
                        'discount_amount'       => $order['totalDiscount'] ?? 0,
                        'discount_inc_tax'      => $order['totalDiscount'] ?? 0,
                        'total_tax'             => 0,
                        'total_alcohol_tax'     => 0,
                        'delivery_fee'          => $order['deliveryCosts'] ?? 0,
                        'total_price'           => $order['totalPrice'] ?? 0,
                        'original_order_total'  => $order['totalPrice'] ?? 0,
                        'order_date'            => $orderDate,
                        'order_time'            => $order_time,
                        'status'                => 'paid',
                        'payment_method_type'   => '',
                        'method'                => $order['paymentMethod'] ?? '',
                        'paid_on'               => Carbon::now()->format('Y-m-d H:i:s'),
                        'order_type'            => $order['orderType'] ?? '',
                        'order_status'          => 'received',
                        'comment'               => $order['remark'] ?? '',
                        'original_date'         => $order['orderDate'] ?? '',
                        'order_key'             => $order['orderKey'] ?? '',
                        'created_from'          => 'cron',
                    ];
                    if (isset($order['customer']) && $order['customer']) {
                        $street = $order['customer']['street'] ?? '';
                        $streetNumber = $order['customer']['streetNumber'] ?? '';
                        $city = $order['customer']['city'] ?? '';
                        $orderData['delivery_address'] = $street.' '.$streetNumber.', '.$city.', Netherlands';
                        $orderData['delivery_postcode'] = $order['customer']['postalCode'] ?? '';
                        $orderData['delivery_place'] = $order['customer']['companyName'] ?? '';
                        $orderData['first_name'] = $order['customer']['name'] ?? '';
                        $orderData['contact_no'] = $order['customer']['phoneNumber'] ?? '';
                    }
                    $orderItemData = [];
                    if (isset($order['products']) && count($order['products']) > 0) {
                        foreach ($order['products'] as $pro_key => $product) {
                            $orderItemData[$pro_key] = [
                                'product_id'   => $product['id'] ?? '',
                                'product_name' => $product['name'] ?? '',
                                'quantity'     => $product['count'] ?? '',
                                'unit_price'   => $product['price'] ?? 0,
                                'comment'      => $product['remark'] ?? '',
                            ];
                            $sideDish_input = [];
                            if (isset($product['sideDishes']) && $product['sideDishes']) {
                                foreach ($product['sideDishes'] as $dish_key => $sideDish) {
                                    $sideDish_input[$dish_key] = [
                                        'id'   => $sideDish['id'] ?? '',
                                        'name' => $sideDish['name'] ?? '',
                                        'val'  => $sideDish['price'] ?? '',
                                    ];
                                }
                            }
                            $orderItemData[$pro_key]['supplements'] = $sideDish_input;
                        }
                    }
                    $orderData['items'] = $orderItemData;
                    $this->orderDataStoreWise[$storeId][] = $orderData;
                } catch (Exception $e) {
                }
            }
        }
    }

    private function saveOrdersIntoDatabase()
    {
        try {
            foreach ($this->orderDataStoreWise as $orders) {
                foreach ($orders as $order) {
                    try {
                        $order_exist = Order::query()
                            ->where('thusibezorgd_order_id', $order['thusibezorgd_order_id'])
                            ->where('thusibezorgd_res_id', $order['thusibezorgd_res_id'])
                            ->first();
                        $order_history_exist = OrderHistory::query()
                            ->where('thusibezorgd_order_id', $order['thusibezorgd_order_id'])
                            ->where('thusibezorgd_res_id', $order['thusibezorgd_res_id'])
                            ->first();
                        if (empty($order_exist) && empty($order_history_exist)) {
                            $orderData['table_name'] = '';
                            $orderData['is_takeaway_mail_send'] = 0;
                            $currentOrderItems = [];
                            foreach ($orderData['items'] as $key => $item) {
                                $sup_items = [];
                                if (isset($item['supplements']) && count($item['supplements']) > 0) {
                                    foreach ($item['supplements'] as $supplement) {
                                        $sup = Supplement::query()
                                            ->where('store_id', $orderData['store_id'])
                                            ->where('thuisbezorgd_id', $supplement['id'])
                                            ->whereNull('deleted_at') //need to remove after add soft deleted
                                            ->first();
                                        if ($sup) {
                                            $supplement['id'] = $sup->id;
                                            $supplement['categoryId'] = null;
                                            $supplement['alt_name'] = isset($sup->alt_name) ? $sup->alt_name : null;
                                            $sup_items[] = $supplement;
                                        }
                                    }
                                }
                                $item['extra'] = json_encode([
                                    'serve_type'  => [],
                                    'size'        => [],
                                    'supplements' => $sup_items,
                                    'users'       => [],
                                ]);
                                unset($item['supplements']);
                                $product = Product::query()->where('store_id', $orderData['store_id'])
                                    ->where('thuisbezorgd_id', $item['product_id'])
                                    ->whereNull('deleted_at') //need to remove after add soft deleted
                                    ->first();
                                if ($product) {
                                    $item['product_id'] = $product->id;
                                    $currentOrderItems[] = $item;
                                }
                            }
                            $orderData['items'] = $currentOrderItems;
                            $this->createOrderAndOrderItems($orderData);
                        }
                    } catch (Exception $e) {
                    }
                }
            }
        } catch (Exception $e) {
        }
    }

    /**
     * @param $orderData
     *
     * @return void
     */
    private function createOrderAndOrderItems($orderData)
    {
        try {
            $items = $orderData['items'];
            unset($orderData['items']);
            $order = Order::create($orderData);
            foreach ($items as $key => $item) {
                $items[$key]['order_id'] = $order->id;
            }
            OrderItem::insert($items);
            // send multiple events
            $store = collect($this->stores)->where('id', $orderData['store_id'])->first();
            $client = new Client([
                'auth'    => [
                    $store->StoreThuisbezorgd->user_name,
                    $store->StoreThuisbezorgd->password,
                ],
                'headers' => ['Apikey' => $store->StoreThuisbezorgd->api_key],
            ]);
            if (isset($orderData['original_date']) && strtotime($orderData['original_date'])) {
                $this->sendOrderConfirmationToThusibezorgd($orderData, $client);
                $this->sendPrintedEventToThusibezorgd($orderData, $client);
                $this->sendOrderConfirmedDeliveryTimeToThusibezorgd($orderData, $client);
            }
            $this->createNotificationForThusibezorgd($order, $store);
        } catch (Exception $e) {
        }
    }

    /**
     * @param $orderData
     *
     * @return void
     */
    private function sendOrderConfirmationToThusibezorgd($orderData, $client)
    {
        try {
            //received order
            $request = $client->request('POST', 'https://posapi.takeaway.com/1.0/status', [
                'json' => [
                    'id'     => $orderData['thusibezorgd_order_id'],
                    'key'    => $orderData['order_key'],
                    'status' => 'received',
                ],
            ]);
            $statusCode = $request->getStatusCode();
        } catch (GuzzleException $e) {
        }
    }

    /**
     * @param $orderData
     * @param $client
     *
     * @return void
     */
    private function sendPrintedEventToThusibezorgd($orderData, $client)
    {
        try {
            //printed order
            $request = $client->request('POST', 'https://posapi.takeaway.com/1.0/status', [
                'json' => [
                    'id'     => $orderData['thusibezorgd_order_id'],
                    'key'    => $orderData['order_key'],
                    'status' => 'printed',
                ],
            ]);
            // $statusCode = $request->getStatusCode();
        } catch (GuzzleException $e) {
        }
    }

    /**
     * @param $orderData
     * @param $client
     *
     * @return void
     */
    private function sendOrderConfirmedDeliveryTimeToThusibezorgd($orderData, $client)
    {
        try {
            $orderDate2 = Carbon::parse($orderData['original_date'])->addMinutes(7)->format('Y-m-d H:i:s');
            $request = $client->request('POST', 'https://posapi.takeaway.com/1.0/status', [
                'json' => [
                    'id'                  => $orderData['thusibezorgd_order_id'],
                    'key'                 => $orderData['order_key'],
                    'status'              => 'confirmed_change_delivery_time',
                    'changedDeliveryTime' => $orderDate2,
                ],
            ]);
            $statusCode = $request->getStatusCode();
            $request->getHeaderLine('content-type');
            $response = $request->getBody()->getContents();
        } catch (GuzzleException $e) {
        }
    }

    /**
     * @param $createdOrder
     *
     * @return void
     */
    protected function createNotificationForThusibezorgd($createdOrder, $store)
    {
        $order = $createdOrder->toArray();
        try {
            /*web notification*/
            $createdNotification = Notification::create([
                'store_id'        => $store->id,
                'notification'    => __('messages.new_order_notification', [
                    'order_id' => $order['order_id'],
                    'username' => $order['full_name'],
                ]),
                'type'            => 'order',
                'additional_data' => json_encode([
                    'id'                  => $order['id'],
                    'order_id'            => $order['order_id'],
                    'order_date'          => Carbon::parse($order['order_date'])->format('d-m-Y'),
                    'order_time'          => $order['order_time'] ?? '',
                    'coupon_price'        => $order['coupon_price'] ?? '',
                    'total_price'         => $order['total_price'] ?? '',
                    'order_type'          => $order['order_type'] ?? '',
                    'full_name'           => $order['full_name'] ?? '',
                    'contact_no'          => $order['contact_no'] ?? '',
                    'date'                => $order['order_date'] ?? '',
                    'method'              => $order['method'] ?? '',
                    'payment_method_type' => $order['payment_method_type'] ?? '',
                    'delivery_address'    => $order['delivery_address'] ?? '',
                    'dutch_date'          => appDutchDate($order['order_date']),
                    'is_auto_print'       => $store->is_auto_print_takeaway,
                    'is_notification'     => 1,
                    'uber_eats_order_id'  => $order['uber_eats_order_id'] ?? '',
                ]),
                'read_at'         => null,
            ]);
            $this->socketForThusibezorgd($createdNotification, $store);
            $this->sendJsonToSQSOrSetFuturePrintForThusibezorgd($createdNotification, $store);
        } catch (Exception $e) {
        }
    }

    /**
     * @param $createdNotification
     * @param $store
     *
     * @return void
     */
    private function socketForThusibezorgd($createdNotification, $store)
    {
        $redis = Redis::connection();
        $redis->publish('new_order', json_encode([
            'store_id'        => $store->id,
            'notification_id' => $createdNotification->id,
            'additional_data' => $createdNotification->additional_data,
        ]));
    }

    /**
     * @param $createdOrder
     * @param $store
     *
     * @return void
     */
    protected function sendJsonToSQSOrSetFuturePrintForThusibezorgd($createdOrder, $store)
    {
        try {
            $order = $createdOrder->toArray();
            if ($store->future_order_print_status == 0 || (Carbon::parse($order['order_date'])
                        ->format('Y-m-d') == Carbon::now()
                        ->format('Y-m-d')/* && $order_time_difference <= ($store->future_order_print_time ? $store->future_order_print_time : 0)*/)) {
                if ($store->sqs) {
                    // Todo : get print JSON data from EatcardPrint Service
                    $printRes = [];
                    if (! empty($printRes)) {
                        config([
                            'queue.connections.sqs.region' => $store->sqs->sqs_region,
                            'queue.connections.sqs.queue'  => $store->sqs->sqs_queue_name,
                            'queue.connections.sqs.prefix' => $store->sqs->sqs_url,
                        ]);
                        Queue::connection('sqs')->pushRaw(json_encode($printRes), $store->sqs->sqs_queue_name);
                    }
                }
            } else {
                Order::query()->where('id', $order['id'])->update(['is_future_order_print_pending' => 1]);
            }
        } catch (\Exception $e) {
        }
    }
}
