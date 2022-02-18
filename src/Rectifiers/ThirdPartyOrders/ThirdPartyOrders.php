<?php

namespace Weboccult\EatcardCompanion\Rectifiers\ThirdPartyOrders;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Queue;
use Weboccult\EatcardCompanion\Models\Notification;
use Weboccult\EatcardCompanion\Models\Order;
use Weboccult\EatcardCompanion\Models\OrderHistory;
use Weboccult\EatcardCompanion\Models\Store;
use function Weboccult\EatcardCompanion\Helpers\appDutchDate;

/**
 * @author Darshit Hedpara
 */
abstract class ThirdPartyOrders
{
    /** @var Store|null|object */
    protected ?Store $store;

    /** @var Order|OrderHistory|null|object */
    protected $createdOrder = null;

    /** @var Notification|null|object */
    protected $createdNotification = null;

    /** @var array */
    protected array $orderData = [];

    /** @var array<array> */
    protected $orderItemData = [];

    abstract public function handle(array $data);

    // abstract public function sendNotification();
    protected function createNotification()
    {
        $order = $this->createdOrder->toArray();
        try {
            /*web notification*/
            $this->createdNotification = Notification::create([
                'store_id'        => $this->store->id,
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
                    'is_auto_print'       => $this->store->is_auto_print_takeaway,
                    'is_notification'     => 1,
                    'uber_eats_order_id'  => $order['uber_eats_order_id'] ?? '',
                ]),
                'read_at'         => null,
            ]);
        } catch (Exception $e) {
        }
    }

    protected function sendJsonToSQSOrSetFuturePrint()
    {
        try {
            $order = $this->createdOrder->toArray();
            if ($this->store->future_order_print_status == 0 || (Carbon::parse($order['order_date'])
                        ->format('Y-m-d') == Carbon::now()
                        ->format('Y-m-d')/* && $order_time_difference <= ($this->store->future_order_print_time ? $this->store->future_order_print_time : 0)*/)) {
                if ($this->store->sqs) {
                    // Todo : get print JSON data from EatcardPrint Service
                    $printRes = [];
                    if ($printRes && ! empty($printRes)) {
                        config([
                            'queue.connections.sqs.region' => $this->store->sqs->sqs_region,
                            'queue.connections.sqs.queue'  => $this->store->sqs->sqs_queue_name,
                            'queue.connections.sqs.prefix' => $this->store->sqs->sqs_url,
                        ]);
                        Queue::connection('sqs')->pushRaw(json_encode($printRes), $this->store->sqs->sqs_queue_name);
                    }
                }
            } else {
                Order::query()->where('id', $order['id'])->update(['is_future_order_print_pending' => 1]);
            }
        } catch (\Exception $e) {
        }
    }
}
