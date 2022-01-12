<?php

namespace Weboccult\EatcardCompanion\Helpers;

use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Weboccult\EatcardCompanion\Models\Message;
use Weboccult\EatcardCompanion\Models\Notification;
use Weboccult\EatcardCompanion\Models\Order;
use Weboccult\EatcardCompanion\Models\OrderHistory;
use Weboccult\EatcardCompanion\Models\ReservationOrderItem;
use Weboccult\EatcardCompanion\Models\ReservationTable;
use Weboccult\EatcardCompanion\Models\Supplement;
use Illuminate\Support\Facades\Redis as LRedis;

if (! function_exists('eatcardSayHello')) {
    /**
     * @description testing helper function.
     *
     * @param $string
     *
     * @return string
     */
    function eatcardSayHello($string): string
    {
        return $string;
    }
}

if (! function_exists('splitDigits')) {
    /**
     * @description Split and return split or diff od split value
     *
     * @param $val
     * @param int $split_digit
     * @param bool $return_diff
     * @param int $return_digit
     * @param int $payment_digit
     *
     * @return float|int $rounded_digit
     */
    function splitDigits($val, int $split_digit = 4, bool $return_diff = false, int $return_digit = 4, int $payment_digit = 2)
    {
        $rounded_digit = 0;
        try {
            $rounded_digit = floatval(bcdiv($val, 1, $split_digit));
            if ($return_diff) {
                $payment_amount = floatval(bcdiv($val, 1, $payment_digit));
                $round_return = floatval(bcdiv($val, 1, $split_digit));

                return round($payment_amount - $round_return, $return_digit);
            }

            return $rounded_digit;
        } catch (Exception $exception) {
            companionLogger(
                'splitDigits function exception',
                'Line - '.$exception->getLine(),
                'Error - '.$exception->getMessage()
            );

            return $rounded_digit;
        }
    }
}

if (! function_exists('getDutchDate')) {
    /**
     * @param string $date
     * @param bool $day2let
     *
     * @return string
     * @Description
     */
    function getDutchDate(string $date, bool $day2let = false): string
    {
        $dutchDayNames = [
            'Sunday' => 'zondag',
            'Monday' => 'maandag',
            'Tuesday' => 'dinsdag',
            'Wednesday' => 'woensdag',
            'Thursday' => 'donderdag',
            'Friday' => 'vrijdag',
            'Saturday' => 'zaterdag',
        ];
        $monthNames = [
            'January' => 'januari',
            'February' => 'februari',
            'March' => 'maart',
            'April' => 'april',
            'May' => 'mei',
            'June' => 'juni',
            'July' => 'juli',
            'August' => 'augustus',
            'September' => 'september',
            'October' => 'oktober',
            'November' => 'november',
            'December' => 'december',
        ];
        $day = $day2let ?
            appDutchDay2Letter(Carbon::parse($date)->format('l')) :
            $dutchDayNames[Carbon::parse($date)->format('l')];

        return $day.' '.Carbon::parse($date)->format('d').' '.$monthNames[Carbon::parse($date)
                ->format('F')].' '.Carbon::parse($date)->format('Y');
    }
}

if (! function_exists('getDutchDate')) {
    /**
     * @param string $day
     *
     * @return string
     */
    function appDutchDay2Letter(string $day): string
    {
        $dutchDayNames = [
            'Sunday' => 'Zo',
            'Monday' => 'Ma',
            'Tuesday' => 'Di',
            'Wednesday' => 'Wo',
            'Thursday' => 'Do',
            'Friday' => 'Vr',
            'Saturday' => 'Za',
        ];

        return $dutchDayNames[$day];
    }
}

if (! function_exists('appDutchDate')) {
    /**
     * @param $date
     *
     * @return string
     */
    function appDutchDate($date): string
    {
        $dutchDayNames = [
            'Sunday' => 'Zo',
            'Monday' => 'Ma',
            'Tuesday' => 'Di',
            'Wednesday' => 'Wo',
            'Thursday' => 'Do',
            'Friday' => 'Vr',
            'Saturday' => 'Za',
        ];

        return $dutchDayNames[Carbon::parse($date)->format('l')].' '.Carbon::parse($date)->format('d-m-y');
    }
}

if (! function_exists('getS3File')) {

    /**
     * @param string $path
     *
     * @return string
     * @Description get file from given path (s3)
     */
    function getS3File(string $path = ''): string
    {
        if ($path) {
            return $path;
        } else {
            return env('AWS_URL').'assets/no_image.png';
        }
    }
}

if (! function_exists('generatePOSOrderId')) {
    /**
     * @param $store_id
     *
     * @return int|string
     */
    function generatePOSOrderId($store_id)
    {
        $order_history = OrderHistory::query()
            ->where('store_id', $store_id)
            ->where('order_type', 'pos')
            ->where(function ($q) {
                $q->where(DB::raw('LENGTH(order_id)'), '=', '8')
                  ->where('order_id', 'LIKE', Carbon::now()->format('y').'0%');
            })
            ->whereNotBetween('created_at', [
                '2021-03-11 00:00:00',
                '2021-03-12 23:59:59',
            ])
            ->orderBy('created_at', 'desc')
            ->first();
        if ($order_history) {
            $order_history->makeHidden(['full_name']);
        }
        $order = Order::query()
                        ->where('store_id', $store_id)
                        ->where('order_type', 'pos')
                        ->where(function ($q) {
                            $q->where(DB::raw('LENGTH(order_id)'), '=', '8')
                              ->where('order_id', 'LIKE', Carbon::now()->format('y').'0%');
                        })
                        ->orderBy('created_at', 'desc')
                        ->first();

        if ($order) {
            $order->makeHidden(['full_name']);
        }
        if ($order && $order_history) {
            if ($order_history->id > $order->id) {
                $order = $order_history;
            }
        } elseif ($order_history) {
            $order = $order_history;
        }

        return $order ? $order->order_id + 1 : date('y').'000001';
    }
}

if (! function_exists('getAycePrice')) {
    /**
     * @param array $ayce_data
     *
     * @return float|int
     * @Description calculate ayce price if reservation is all_you_eat
     */
    function getAycePrice(array $ayce_data)
    {
        $ayce_amount = 0;
        if (isset($ayce_data['dinein_price'])) {
            $ayce_amount += isset($ayce_data['no_of_adults']) ? (int) $ayce_data['no_of_adults'] * (isset($ayce_data['dinein_price']['price']) ? (float) $ayce_data['dinein_price']['price'] : 0) : 0;
            $ayce_amount += isset($ayce_data['no_of_kids2']) ? (int) $ayce_data['no_of_kids2'] * (isset($ayce_data['dinein_price']['child_price_2']) ? (float) $ayce_data['dinein_price']['child_price_2'] : 0) : 0;
            if (isset($ayce_data['dinein_price']['is_per_year']) && $ayce_data['dinein_price']['is_per_year']) {
                if (isset($ayce_data['kids_age']) && count($ayce_data['kids_age']) > 0) {
                    $min_age = (int) $ayce_data['dinein_price']['min_age'];
                    $max_age = (int) $ayce_data['dinein_price']['max_age'];
                    $kid_price = (float) $ayce_data['dinein_price']['child_price'];
                    foreach ($ayce_data['kids_age'] as $kids_age) {
                        $ayce_amount += ((((int) $kids_age) - $min_age) + 1) * $kid_price;
                    }
                }
            } else {
                $ayce_amount += isset($ayce_data['no_of_kids']) ? (int) $ayce_data['no_of_kids'] * (isset($ayce_data['dinein_price']['child_price']) ? (float) $ayce_data['dinein_price']['child_price'] : 0) : 0;
            }
            if (isset($ayce_data['dinein_price']['dynamic_prices'])) {
                foreach ($ayce_data['dinein_price']['dynamic_prices'] as $dynamic_prices) {
                    if (isset($dynamic_prices['person'])) {
                        $ayce_amount += (int) $dynamic_prices['person'] * (float) $dynamic_prices['price'];
                    }
                }
            }
        }

        return $ayce_amount;
    }
}

if (! function_exists('discountCalc')) {
    /**
     * @param $total_value
     * @param $base_value
     * @param $discount_type
     * @param $discount
     * @param int $cart_total
     *
     * @return float|int|void
     */
    function discountCalc($total_value, $base_value, $discount_type, $discount, int $cart_total = 0)
    {
        try {
            $calculated_amount = 0;
            $discount = (float) $discount;
            if ($total_value == $base_value && $discount_type == 1 && $cart_total == 0) {
                return $discount;
            }
            if ($discount_type == 1 && $cart_total == 0) { // EURO (amount) discount
                $discount_per = round(($discount * 100) / $total_value, 5);
            } elseif ($discount_type == 1 && $cart_total > 0) {
                $discount_per = round(($discount * 100) / $cart_total, 5);
            } else {
                $discount_per = $discount;
            }
            if ($discount_per > 0) {
                $calculated_amount = ($base_value * $discount_per) / 100;
            }

            return $calculated_amount;
        } catch (\Exception $e) {
            companionLogger(
                'discountCalc - helper',
                'error : '.$e->getMessage(),
                'IP address : '.request()->ip(),
                'browser : '.request()->header('User-Agent')
            );
        }
    }
}

if (! function_exists('cartTotalValueCalc')) {
    /**
     * @param $items
     * @param $products
     * @param $res
     * @param $ayce_amount
     *
     * @return float|int|void
     */
    function cartTotalValueCalc($items, $products, $res, $ayce_amount)
    {
        try {
            $product_total = 0;
            foreach ($items as $key => $item) {
                /*This variable used for checked product and it/s supplement price count or not in total price*/
                $is_product_chargable = true;
                companionLogger('item id : ', $item['id']);
                $product = $products->where('id', $item['id'])->first();
                $isVoided = (isset($item['void_id']) && $item['void_id'] != '');
                $isOnTheHouse = (isset($item['on_the_house']) && $item['on_the_house'] == '1');
                // if product is void and onthenhouse then no need to calculate.
                if ($isVoided || $isOnTheHouse) {
                    continue;
                }
                $aycePrice = 0;
                if (isset($res) && $res) {
                    //check ayce price
                    if ((isset($product->ayce_class) && ! empty($product->ayce_class)) && $product->ayce_class->count() > 0 && $res->dineInPrice && isset($res->dineInPrice->dinein_category_id) && $res->dineInPrice->dinein_category_id != '') {
                        $ayeClasses = $product->ayce_class->pluck('dinein_category_id')->toArray();
                        if (! empty($ayeClasses) && in_array($res->dineInPrice->dinein_category_id, $ayeClasses) /*&& $product->all_you_can_eat_price > 0*/) {
                            $ayce_individual_price = $product->ayce_class->where('dinein_category_id', $res->dineInPrice->dinein_category_id)
                                ->pluck('price');
                            if (isset($ayce_individual_price[0]) && $ayce_individual_price[0] > 0 && ! empty($ayce_individual_price[0]) && $product->all_you_can_eat_price >= 0) {
                                $aycePrice = $ayce_individual_price[0];
                            } else {
                                if (! empty($product->all_you_can_eat_price)) {
                                    $aycePrice = $product->all_you_can_eat_price;
                                }
                            }
                        }
                    } else {
                        /*If res type is cart then get product price from pieces*/
                        if (isset($product->total_pieces) && $product->total_pieces != '' && isset($product->pieces_price) && $product->pieces_price != '' && $res->reservation_type != 'all_you_eat') {
                            $aycePrice = (float) $product->pieces_price;
                        }
                    }
                }
                companionLogger('Product ayce price : ', $aycePrice);
                if ($aycePrice) {
                    //if there is ayce price
                    $product->price = $aycePrice;
                } else {
                    /*check for if the category is free or not if the order is from the all you cat eat functionality*/
                    if (! $item['base_price']) {
                        $product->price = 0;
                        $is_product_chargable = false;
                    } else {
                        $product->price = (! is_null($product->discount_price) && $product->discount_show) ? $product->discount_price : $product->price;
                    }
                }
                companionLogger('Product price', $product->name, $product->price);
                if ($product->price > 0 || $is_product_chargable) {
                    $supplement_total = 0;
                    $supplements = [];
                    if (isset($item['supplements'])) {
                        $supplement_ids = collect($item['supplements'])->pluck('id')->toArray();
                        $supplements = Supplement::withTrashed()->whereIn('id', $supplement_ids)->get();
                        $newSup = [];
                        foreach (collect($item['supplements']) as $i) {
                            $isExist = collect($newSup)->search(function ($item) use ($i) {
                                return $item['id'] == $i['id'] && $item['val'] == $i['val'];
                            });
                            if ($isExist && $i['val'] == $newSup[$isExist]['val']) {
                                $newSup[$isExist]['qty'] += 1;
                                $newSup[$isExist]['total_val'] = $newSup[$isExist]['val'] * $newSup[$isExist]['qty'];
                            } else {
                                $newSup[] = [
                                    'id'        => $i['id'],
                                    'name'      => $i['name'],
                                    'val'       => $i['val'] ?? 0,
                                    'total_val' => $i['val'] ?? 0,
                                    'qty'       => isset($i['qty']) && $i['qty'] ? $i['qty'] : 1,
                                ];
                            }
                        }
                        $item['supplements'] = $newSup;
                    }
                    foreach ($item['supplements'] as $supp) {
                        $currentSup = collect($supplements)->where('id', $supp['id'])->first();
                        if ($supp['val'] != 0) {
                            $supplement_total += $currentSup->price * $supp['qty'];
                        }
                    }
                    $size_total = 0;
                    if (isset($item['size']) && $item['size']) {
                        if ($item['size']['name'] == 'large') {
                            $size_total = $product->large_price;
                        } elseif ($item['size']['name'] == 'regular') {
                            $size_total = $product->regular_price;
                        }
                    }
                    $weight_total = $product->price;
                    if (isset($item['weight']) && $item['weight']) {
                        $weight_total = ((int) $item['weight'] * $product->price) / $product->weight;
                    }
                    if ($is_product_chargable) {
                        $product_total += ($supplement_total + $size_total + $weight_total) * $item['quantity'];
                    } else {
                        $product_total += 0;
                    }
                }
            }
            $product_total += ! empty($ayce_amount) ? (float) $ayce_amount : 0;

            return $product_total;
        } catch (Exception $e) {
            companionLogger(
                'cartTotalValueCalc',
                'error - '.$e->getMessage(),
                'IP address - '.request()->ip(),
                'Browser - '.request()->header('User-Agent'),
                'Line - '.$e->getLine()
            );
        }
    }
}

if (! function_exists('changePriceFormat')) {
    /**
     * @param $val
     *
     * @return string
     */
    function changePriceFormat($val): string
    {
        return ($val) ? number_format((float) $val, 2, ',', '') : '0,00';
    }
}

if (! function_exists('sendResWebNotification')) {
    /**
     * @param $id
     * @param $store_id
     * @param string $channel
     * @param array $oldTables
     * @param int $reload
     *
     * @return bool|void
     */
    function sendResWebNotification($id, $store_id, string $channel = '', array $oldTables = [], int $reload = 1)
    {
        try {
            /*web notification*/
            $reservation = ReservationTable::whereHas('reservation', function ($q) use ($store_id) {
                $q/*->whereHas('meal')*/ ->where('status', 'approved');
            })->with([
                'reservation' => function ($q) use ($store_id) {
                    $q->with([
                        'meal:id,name,time_limit',
                        'tables.table',
                        'user' => function ($q2) use ($store_id) {
                            $q2->select('id', 'profile_img')->with([
                                'card' => function ($q3) use ($store_id) {
                                    $q3->select('id', 'customer_id', 'total_points')
                                        ->where('store_id', $store_id)
                                        ->where('status', 'active');
                                },
                            ]);
                        },
                    ])->where('status', 'approved')->where(function ($q1) {
                        $q1->whereIn('payment_status', [
                            'paid',
                            '',
                        ])->orWhere('total_price', null);
                    });
                },
            ])->where('reservation_id', $id)->first();
            if ($reservation && $reservation->reservation) {
                $reservation->reservation->end = 120;
                if ($reservation->reservation->is_dine_in || $reservation->reservation->is_qr_scan) {
                    $orders = Order::with('orderItems.product:id,image,sku')
                        ->where('status', 'paid')
                        ->where('parent_id', $reservation->reservation->id)
                        ->orderBy('id', 'desc')
                        ->get()
                        ->toArray();
                    foreach ($orders as $key => $order) {
                        $orders[$key]['dutch_order_status'] = __('messages.'.$order['order_status']);
                    }
                    $reservation->reservation->orders = $orders;
                }
                if ($reservation->reservation->end_time) {
                    if ($reservation->reservation->end_time == '00:00') {
                        $reservation->reservation->end_time = '24:00';
                    }
                    $start = Carbon::parse($reservation->reservation->from_time)->format('H:i');
                    $reservation->reservation->end = Carbon::parse($reservation->reservation->end_time)
                        ->diffInMinutes($start);
                } elseif (isset($reservation->reservation->meal)) {
                    $reservation->reservation->end = $reservation->reservation->meal->time_limit;
                }
                if (isset($reservation->reservation->user) && $reservation->reservation->user != null && isset($reservation->reservation->user->profile_img) && file_exists(public_path($reservation->reservation->user->profile_img))) {
                    $reservation->reservation->user_profile_image = isset($reservation->reservation->user->profile_img) ? asset($reservation->reservation->user->profile_img) : asset('asset_new/app/media/img/users/user4.jpg');
                }
                if ($reservation->reservation->voornaam || $reservation->reservation->achternaam) {
                    $reservation->reservation->img_name = strtoupper(mb_substr($reservation->reservation->voornaam, 0, 1).mb_substr($reservation->reservation->achternaam, strrpos($reservation->reservation->achternaam, ' '), 1));
                } else {
                    $reservation->reservation->img_name = 'G';
                }
                /*get unread message threads of the logged in user*/
                $threadIds = \Cmgmyr\Messenger\Models\Thread::forUserWithNewMessages(auth()->id())
                    ->latest('updated_at')
                    ->get()
                    ->pluck('id')
                    ->toArray();
                $reservation->reservation->unread_msg = false;
                if (in_array($reservation->reservation->thread_id, $threadIds)) {
                    $reservation->reservation->unread_msg = true;
                }
                $reservation->reservation->messages = Message::select('id', 'body', 'created_at', 'user_id')
                    ->where('thread_id', $reservation->reservation->thread_id)
                    ->get();
                if ($reservation->reservation->group_id) {
                    $all_tables = $reservation->reservation->tables->pluck('table.name')->toArray();
                    $reservation->reservation->all_tables = $all_tables;
                }
                if (isset($reservation->reservation->tables) && $reservation->reservation->tables->count() > 0) {
                    $tables = [];
                    foreach ($reservation->reservation->tables as $table) {
                        if ($table->table) {
                            $tables[] = $table->table->name;
                        }
                    }
                    $reservation->reservation->table_names = isset($tables) ? implode('  ', $tables) : '';
                }
                unset($reservation->reservation->tables);
                $temp_orders = [];
                if ($reservation->is_dine_in == 1) {
                    $temp_orders = Order::with('orderItems.product:id,image,sku')
                        ->where('status', 'paid')
                        ->where('parent_id', $id)
                        ->orderBy('id', 'desc')
                        ->get()
                        ->toArray();
                }
                if ($reservation->is_dine_in == 0) {
                    $orders = ReservationOrderItem::query()
                        ->where('reservation_id', $id)
                        ->orderBy('round', 'desc')
                        ->get()
                        ->toArray();
                    $temp_orders = collect($orders)->map(function ($item) {
                        $item['cart'] = json_decode($item['cart']);

                        return $item;
                    })->toArray();
                }
                $reservation->reservation->orders = $temp_orders;
                $last_message = Message::query()->where('thread_id', $reservation->reservation->thread_id)
                    ->orderBy('created_at', 'desc')
                    ->first();
                if ($last_message) {
                    $reservation->reservation->last_message = $last_message->body;
                } else {
                    $reservation->reservation->last_message = null;
                }
                if ($reservation->is_dine_in == 1) {
                    $temp_orders = Order::with('orderItems.product:id,image,sku')
                        ->where('status', 'paid')
                        ->where('parent_id', $id)
                        ->orderBy('id', 'desc')
                        ->get()
                        ->toArray();
                }
                if ($reservation->is_dine_in == 0) {
                    $orders = ReservationOrderItem::query()
                        ->where('reservation_id', $id)
                        ->orderBy('round', 'desc')
                        ->get()
                        ->toArray();
                    $temp_orders = collect($orders)->map(function ($item) {
                        $item['cart'] = json_decode($item['cart']);

                        return $item;
                    })->toArray();
                }
                $reservation->reservation->orders = $temp_orders;
                $tempReservation = $reservation->toArray();
                $tempReservation['reservation']['reservation_date'] = $reservation->reservation->getRawOriginal('res_date');
                companionLogger('new booking web notification', $tempReservation);
                $dinein_area_id = '';
                $table = ReservationTable::with(['table'])->where('reservation_id', $id)->first();
                $current_reservation_table = ReservationTable::query()
                    ->where('reservation_id', $id)
                    ->pluck('table_id')
                    ->toArray();
                if ($table && $table['table']) {
                    $dinein_area_id = $table['table']->dining_area_id;
                }
                $additionalData = json_encode([
                    'reservation'    => $tempReservation,
                    'is_reload'      => $reload,
                    'reservation_id' => $reservation->reservation->id,
                    'old_tables'     => $oldTables,
                    'dinein_area_id' => $dinein_area_id,
                    'table_ids'      => $current_reservation_table,
                ]);
                $redis = LRedis::connection();
                $redis->publish('reservation_booking', json_encode([
                    'store_id'        => $store_id,
                    'channel'         => $channel ? $channel : 'new_booking',
                    'notification_id' => 0,
                    'additional_data' => $additionalData,
                ]));
            }

            return true;
        } catch (\Exception $e) {
            companionLogger('new booking web notification error', 'error : '.$e->getMessage(), 'file : '.$e->getFile(), 'line : '.$e->getLine(), );
        }
    }
}

if (! function_exists('getChatMsgDateTimeFormat')) {
    /**
     * @param $date
     *
     * @return string
     */
    function getChatMsgDateTimeFormat($date): string
    {
        $dutchDayNames = [
            'Sunday'    => 'zondag',
            'Monday'    => 'maandag',
            'Tuesday'   => 'dinsdag',
            'Wednesday' => 'woensdag',
            'Thursday'  => 'donderdag',
            'Friday'    => 'vrijdag',
            'Saturday'  => 'zaterdag',
        ];
        $monthNames = [
            'January'   => 'januari',
            'February'  => 'februari',
            'March'     => 'maart',
            'April'     => 'april',
            'May'       => 'mei',
            'June'      => 'juni',
            'July'      => 'juli',
            'August'    => 'augustus',
            'September' => 'september',
            'October'   => 'oktober',
            'November'  => 'november',
            'December'  => 'december',
        ];
        $dutchDate = Carbon::parse($date)->format('d').' '.$monthNames[Carbon::parse($date)->format('F')];

        return $dutchDate.' | '.Carbon::parse($date)->format('H:i ');
    }
}

if (! function_exists('sendWebNotification')) {
    function sendWebNotification($store, $order, $data, $is_last_payment = 0, $force_refresh = 0)
    {
        try {
            $notification = Notification::query()->create([
                'store_id'        => $order['store_id'],
                'notification'    => __('messages.new_order_notification', [
                    'order_id' => $order['order_id'],
                    'username' => $order['full_name'],
                ]),
                'type'            => 'order',
                'additional_data' => json_encode([
                    'id'                    => $order['id'],
                    'order_id'              => $order['order_id'],
                    'order_date'            => $order['order_date'],
                    'order_time'            => $order['order_time'],
                    'total_price'           => $order['total_price'],
                    'order_type'            => $order['order_type'] ?? 'pos',
                    'full_name'             => $order['full_name'],
                    'contact_no'            => $order['contact_no'],
                    'order_status'          => $order['order_status'],
                    'table_name'            => $order['table_name'],
                    'dutch_order_status'    => __('messages.'.$order['order_status']),
                    'date'                  => $data['orderDate'],
                    'delivery_address'      => $order['delivery_address'],
                    'method'                => isset($order['payment_split_type']) && $order['payment_split_type'] != '' ? '' : $order['method'],
                    'dutch_date'            => appDutchDate($data['orderDate']),
                    'is_auto_print'         => (/*$store->app_pos_print &&*/ $store->is_auto_print_takeaway),
                    'is_notification'       => ($data['is_notification']),
                    'thusibezorgd_order_id' => $order['thusibezorgd_order_id'] ?? '',
                    'uber_eats_order_id'    => $order['uber_eats_order_id'] ?? '',
                    'status'                => $order['status'] ?? '',
                    'is_last_payment'       => $is_last_payment,
                    'is_split_payment'      => isset($order['payment_split_type']) && $order['payment_split_type'] != '' ? 1 : 0,
                    'parent_order_id'       => $order['parent_order_id'] ?? '',
                    'force_refresh'         => $force_refresh,
                ]),
                'read_at'         => Carbon::now()->format('Y-m-d H:i:s'),
            ]);

            return [
                'store_id'        => $order['store_id'],
                'notification_id' => $notification->id,
                'additional_data' => $notification->additional_data,
            ];
        } catch (\Exception $e) {
            companionLogger('takeaway - web notification', 'error : '.$e->getMessage(), 'file : '.$e->getFile(), 'line : '.$e->getLine(), 'IP address : '.request()->ip(), 'Browser : '.request()->header('User-Agent'), );
        }

        return [];
    }
}

if (! function_exists('phpEncrypt')) {

    /**
     * @param string $simple_string
     *
     * @return string
     * @Description encrypt simple string
     */
    function phpEncrypt(string $simple_string): string
    {
        // Store cipher method
        $ciphering = 'AES-256-CBC';
        // Use OpenSSl encryption method
        // $iv_length = openssl_cipher_iv_length($ciphering);
        $options = 0;
        // Use random_bytes() function which gives
        // randomly 16 digit values
        $encryption_iv = '@eatcard--kiosk@';
        // Alternatively, we can use any 16 digit
        // characters or numeric for iv
        $encryption_key = '!$@#eatcard_kiosk_device_encrypt#@$!';
        // Encryption of string process starts
        return base64_encode(openssl_encrypt($simple_string, $ciphering, $encryption_key, $options, $encryption_iv));
    }
}
