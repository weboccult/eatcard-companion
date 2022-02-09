<?php

namespace Weboccult\EatcardCompanion\Helpers;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Weboccult\EatcardCompanion\Models\Device;
use Weboccult\EatcardCompanion\Models\GeneralNotification;
use Weboccult\EatcardCompanion\Models\Message;
use Weboccult\EatcardCompanion\Models\Notification;
use Weboccult\EatcardCompanion\Enums\PrintMethod;
use Weboccult\EatcardCompanion\Enums\PrintTypes;
use Weboccult\EatcardCompanion\Enums\SystemTypes;
use Weboccult\EatcardCompanion\Models\Order;
use Weboccult\EatcardCompanion\Models\OrderHistory;
use Weboccult\EatcardCompanion\Models\ReservationOrderItem;
use Weboccult\EatcardCompanion\Models\ReservationTable;
use Weboccult\EatcardCompanion\Models\StoreReservation;
use Weboccult\EatcardCompanion\Models\Supplement;
use Weboccult\EatcardCompanion\Services\Common\Prints\Generators\PaidOrderGenerator;
use Weboccult\EatcardCompanion\Services\Common\Prints\Generators\RunningOrderGenerator;
use Weboccult\EatcardCompanion\Services\Common\Prints\Generators\SaveOrderGenerator;
use Weboccult\EatcardCompanion\Services\Common\Prints\Generators\SubOrderGenerator;
use Illuminate\Support\Facades\Redis as LRedis;
use GuzzleHttp\Client;
use Weboccult\EatcardCompanion\Services\Common\Revenue\Generators\DailyRevenueGenerator;
use Weboccult\EatcardCompanion\Services\Common\Revenue\Generators\MonthlyRevenueGenerator;
use Weboccult\EatcardCompanion\Services\Facades\OneSignal;

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
            return config('eatcardCompanion.aws_url').'/assets/no_image.png';
        }
    }
}

if (! function_exists('generateTakeawayOrderId')) {
    /**
     * @param $store_id
     *
     * @return int|string
     */
    function generateTakeawayOrderId($store_id)
    {
        $order_id = Carbon::now()->format('y').'0000001';
        $exit = Order::query()->where('store_id', $store_id)
            ->where('order_id', 'LIKE', Carbon::now()->format('y').'00%')
            ->where('created_at', '>', '2020-06-24 08:30:00')
            ->where('order_type', '<>', 'pos')
            ->whereNull('thusibezorgd_order_id')
            ->orderBy('order_id', 'desc')
            ->first();
        if ($exit) {
            $order_id = $exit->order_id + 1;
        }

        return $order_id;
    }
}

if (! function_exists('generateKioskOrderId')) {
    /**
     * @param $store_id
     *
     * @return int|string
     */
    function generateKioskOrderId($store_id)
    {
        // logic is same so redirect to existing function
        // created separate function, In case If we want to update login for kiosk only.
        return generateTakeawayOrderId($store_id);
    }
}

if (! function_exists('generateDineInOrderId')) {
    /**
     * @param $store_id
     *
     * @return int|string
     */
    function generateDineInOrderId($store_id)
    {
        // logic is same so redirect to existing function
        // created separate function, In case If we want to update login for kiosk only.
        return generateTakeawayOrderId($store_id);
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
if (! function_exists('generateReservationId')) {
    /**
     * @return int
     * @Description generate reservation id
     */
    function generateReservationId(): int
    {
        $id = rand(1111111, 9999999);
        $exist = StoreReservation::query()->where('reservation_id', $id)->first();
        if ($exist) {
            return generateReservationId();
        } else {
            return $id;
        }
    }
}
if (! function_exists('carbonParseAddHoursFormat')) {

    /**
     * @param string $time
     * @param int $hours : Integer
     * @param string $format
     *
     * @return string
     * @Description calculate carbon time
     */
    function carbonParseAddHoursFormat(string $time, int $hours, string $format): string
    {
        return Carbon::parse($time)->addHours($hours)->format($format);
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
        return ($val) && (float) ($val) > 0 ? number_format((float) $val, 2, ',', '') : '0,00';
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
                        $orders[$key]['dutch_order_status'] = __('eatcard-companion::general.'.$order['order_status']);
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
                'notification'    => __('eatcard-companion::general.new_order_notification', [
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
                    'dutch_order_status'    => __('eatcard-companion::general.'.$order['order_status']),
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

if (! function_exists('getDistance')) {
    /**
     * @param $from
     * @param $to
     *
     * @return float|int|string
     */
    function getDistance($from, $to)
    {
        $from = urlencode($from);
        $to = urlencode($to);
        $data = file_get_contents("https://maps.googleapis.com/maps/api/distancematrix/json?units=imperial&origins=$from&destinations=$to&key=".config('app.google_map_api_key'));
        $data = json_decode($data);
        $time = 0;
        $distance = 0;
        companionLogger('distance between store and location : ', json_encode($data), 'IP address :'.request()->ip().', browser', 'Browser : '.request()->header('User-Agent'));
        foreach ($data->rows[0]->elements as $road) {
            if (isset($road->status) && $road->status == 'ZERO_RESULTS') {
                return 'ZERO_RESULTS';
            }
            if (isset($road->distance)) {
                $time += $road->duration->value;
                $distance += $road->distance->value;
            }
        }

        return $distance / 1000;
    }
}

if (! function_exists('checkRadiusDistance')) {
    /**
     * @param $store_address
     * @param $address
     * @param $setting
     * @param $total
     *
     * @return array
     */
    function checkRadiusDistance($store_address, $address, $setting, $total): array
    {
        $distance = getDistance($store_address, $address);
        companionLogger('Distance data fetched if delivery radius is on : ', $distance);
        if ($distance === 'ZERO_RESULTS') {
            return [
                'distance_not_found_error' => 'error',
            ];
        }
        if ($distance > 20) {
            return [
                'distance_error' => 'error',
                'distance'       => $distance,
            ];
        }
        /*check distance and amount related calculation*/
        if ($setting) {
            if ($distance <= 1 && $setting->is_delivery_fee_0_1) {
                $takeaway_data['distance'] = $distance;
                $takeaway_data['delivery'] = (! $setting->delivery_free_amount_0_1 || $setting->delivery_free_amount_0_1 > $total) ? $setting->delivery_fee_0_1 : 0;
                $takeaway_data['delivery_minimum_amount'] = $setting->is_delivery_min_amount_0_1;
            } elseif ($distance > 1 && $distance <= 2 && $setting->is_delivery_fee_1_2) {
                $takeaway_data['distance'] = $distance;
                $takeaway_data['delivery'] = (! $setting->delivery_free_amount_1_2 || $setting->delivery_free_amount_1_2 > $total) ? $setting->delivery_fee_1_2 : 0;
                $takeaway_data['delivery_minimum_amount'] = $setting->is_delivery_min_amount_1_2;
            } elseif ($distance > 2 && $distance <= 4 && $setting->is_delivery_fee_2_4) {
                $takeaway_data['distance'] = $distance;
                $takeaway_data['delivery'] = (! $setting->delivery_free_amount_2_4 || $setting->delivery_free_amount_2_4 > $total) ? $setting->delivery_fee_2_4 : 0;
                $takeaway_data['delivery_minimum_amount'] = $setting->is_delivery_min_amount_2_4;
            } elseif ($distance > 4 && $distance <= 6 && $setting->is_delivery_fee_4_6) {
                $takeaway_data['distance'] = $distance;
                $takeaway_data['delivery'] = (! $setting->delivery_free_amount_4_6 || $setting->delivery_free_amount_4_6 > $total) ? $setting->delivery_fee_4_6 : 0;
                $takeaway_data['delivery_minimum_amount'] = $setting->is_delivery_min_amount_4_6;
            } elseif ($distance > 6 && $distance <= 10 && $setting->is_delivery_fee_6_10) {
                $takeaway_data['distance'] = $distance;
                $takeaway_data['delivery'] = (! $setting->delivery_free_amount_6_10 || $setting->delivery_free_amount_6_10 > $total) ? $setting->delivery_fee_6_10 : 0;
                $takeaway_data['delivery_minimum_amount'] = $setting->is_delivery_min_amount_6_10;
            } elseif ($distance > 10 && $distance <= 15 && $setting->is_delivery_fee_10_15) {
                $takeaway_data['distance'] = $distance;
                $takeaway_data['delivery'] = (! $setting->delivery_free_amount_10_15 || $setting->delivery_free_amount_10_15 > $total) ? $setting->delivery_fee_10_15 : 0;
                $takeaway_data['delivery_minimum_amount'] = $setting->is_delivery_min_amount_10_15;
            } elseif ($distance > 15 && $distance <= 20 && $setting->is_delivery_fee_15_20) {
                $takeaway_data['distance'] = $distance;
                $takeaway_data['delivery'] = (! $setting->delivery_free_amount_15_20 || $setting->delivery_free_amount_15_20 > $total) ? $setting->delivery_fee_15_20 : 0;
                $takeaway_data['delivery_minimum_amount'] = $setting->is_delivery_min_amount_15_20;
            } else {
                $takeaway_data['error'] = $distance;
            }

            return $takeaway_data;
        } else {
            return [
                'error' => 'Setting is not available',
            ];
        }
    }
}

if (! function_exists('latterToNumber')) {
    /**
     * @param $letters
     *
     * @return float|int|mixed
     */
    function latterToNumber($letters)
    {
        $alphabet = range('a', 'z');
        $number = 0;
        foreach (str_split(strrev($letters)) as $key => $char) {
            $number = $number + (array_search($char, $alphabet) + 1) * pow(count($alphabet), $key);
        }

        return $number;
    }
}

if (! function_exists('checkZipCodeRange')) {
    /**
     * @param $user_zip_code
     * @param $from_zip_code
     * @param $to_zip_code
     *
     * @return bool
     */
    function checkZipCodeRange($user_zip_code, $from_zip_code, $to_zip_code): bool
    {
        $user_zip_code = str_split($user_zip_code, 1);
        $from_zip_code = str_split($from_zip_code, 1);
        $to_zip_code = str_split($to_zip_code, 1);
        $user_zip_code_string = '';
        $from_zip_code_string = '';
        $to_zip_code_string = '';
        if (($from_zip_code == $user_zip_code || $to_zip_code == $user_zip_code)) {
            return true;
        }
        if (count($user_zip_code) == count($from_zip_code) && count($user_zip_code) == count($to_zip_code)) {
            for ($j = 0; $j < count($user_zip_code); $j++) {
                $user_zip_code_string .= (is_numeric($user_zip_code[$j])) ? 'N' : 'A';
                $from_zip_code_string .= (isset($from_zip_code[$j]) && is_numeric($from_zip_code[$j])) ? 'N' : 'A';
                $to_zip_code_string .= (isset($to_zip_code[$j]) && is_numeric($to_zip_code[$j])) ? 'N' : 'A';
            }
            /*If zip code format is same then check zipcode in given range*/
            if ($user_zip_code_string == $from_zip_code_string && $user_zip_code_string == $to_zip_code_string) {
                $user_zip_code_arr = [];
                $from_zip_code_arr = [];
                $to_zip_code_arr = [];
                for ($i = 0; $i < count($user_zip_code); $i++) {
                    if (empty($user_zip_code_arr)) {
                        $user_zip_code_arr[] = $user_zip_code[$i];
                        $from_zip_code_arr[] = $from_zip_code[$i];
                        $to_zip_code_arr[] = $to_zip_code[$i];
                    } else {
                        if (is_numeric($user_zip_code_arr[count($user_zip_code_arr) - 1]) && is_numeric($user_zip_code[$i])) {
                            $user_zip_code_arr[count($user_zip_code_arr) - 1] .= $user_zip_code[$i];
                            $from_zip_code_arr[count($from_zip_code_arr) - 1] .= $from_zip_code[$i];
                            $to_zip_code_arr[count($to_zip_code_arr) - 1] .= $to_zip_code[$i];
                        }
                        if (! is_numeric($user_zip_code_arr[count($user_zip_code_arr) - 1]) && ! is_numeric($user_zip_code[$i])) {
                            $user_zip_code_arr[count($user_zip_code_arr) - 1] .= $user_zip_code[$i];
                            $from_zip_code_arr[count($from_zip_code_arr) - 1] .= $from_zip_code[$i];
                            $to_zip_code_arr[count($to_zip_code_arr) - 1] .= $to_zip_code[$i];
                        }
                        if (is_numeric($user_zip_code_arr[count($user_zip_code_arr) - 1]) && ! is_numeric($user_zip_code[$i])) {
                            $user_zip_code_arr[] = $user_zip_code[$i];
                            $from_zip_code_arr[] = $from_zip_code[$i];
                            $to_zip_code_arr[] = $to_zip_code[$i];
                        }
                        if (! is_numeric($user_zip_code_arr[count($user_zip_code_arr) - 1]) && is_numeric($user_zip_code[$i])) {
                            $user_zip_code_arr[] = $user_zip_code[$i];
                            $from_zip_code_arr[] = $from_zip_code[$i];
                            $to_zip_code_arr[] = $to_zip_code[$i];
                        }
                    }
                }
                $from_zipcode_greater_than_user_zipcode = true;
                $same_from_user_value = true;
                for ($i = 0; $i < count($user_zip_code_arr); $i++) {
                    if ($same_from_user_value) {
                        if (! is_numeric($user_zip_code_arr[$i])) {
                            $from_value = latterToNumber($from_zip_code_arr[$i]);
                            $user_value = latterToNumber($user_zip_code_arr[$i]);
                        } else {
                            $from_value = $from_zip_code_arr[$i];
                            $user_value = $user_zip_code_arr[$i];
                        }
                        if ($from_value < $user_value && $same_from_user_value) {
                            $from_zipcode_greater_than_user_zipcode = true;
                            $same_from_user_value = false;
                        } elseif ($from_value == $user_value && $same_from_user_value) {
                            $from_zipcode_greater_than_user_zipcode = true;
                            $same_from_user_value = true;
                        } elseif ($from_value > $user_value && $same_from_user_value) {
                            $from_zipcode_greater_than_user_zipcode = false;
                            $same_from_user_value = false;
                        }
                    }
                }
                if ($from_zipcode_greater_than_user_zipcode) {
                    $to_zipcode_greater_than_user_zipcode = true;
                    $same_to_user_value = true;
                    for ($i = 0; $i < count($user_zip_code_arr); $i++) {
                        if ($same_to_user_value) {
                            if (! is_numeric($user_zip_code_arr[$i])) {
                                $to_value = latterToNumber($to_zip_code_arr[$i]);
                                $user_value = latterToNumber($user_zip_code_arr[$i]);
                            } else {
                                $to_value = $to_zip_code_arr[$i];
                                $user_value = $user_zip_code_arr[$i];
                            }
                            if ($to_value > $user_value && $same_to_user_value) {
                                $to_zipcode_greater_than_user_zipcode = true;
                                $same_to_user_value = false;
                            } elseif ($to_value == $user_value && $same_to_user_value) {
                                $to_zipcode_greater_than_user_zipcode = true;
                                $same_to_user_value = true;
                            } elseif ($to_value < $user_value && $same_to_user_value) {
                                $same_to_user_value = false;
                                $to_zipcode_greater_than_user_zipcode = false;
                            }
                        }
                    }
                    if ($to_zip_code == $user_zip_code) {
                        $to_zipcode_greater_than_user_zipcode = true;
                    }
                    if ($to_zipcode_greater_than_user_zipcode && $from_zipcode_greater_than_user_zipcode) {
                        return true;
                    } else {
                        return false;
                    }
                } else {
                    return false;
                }
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
}

if (! function_exists('getDistanceBetweenTwoPoints')) {
    /**
     * Calculates the great-circle distance between two points, with
     * the Haversine formula.
     *
     * @param $lat1
     * @param $lon1
     * @param $lat2
     * @param $lon2
     * @param string $unit
     *
     * @return float Distance between points in [m] (same as earthRadius)
     */
    function getDistanceBetweenTwoPoints($lat1, $lon1, $lat2, $lon2, $unit = '')
    {
        $data = [];
        try {
            $client = new Client();
            $request = $client->request('GET', 'https://maps.googleapis.com/maps/api/distancematrix/json?units=imperial&origins='.$lat1.','.$lon1.'&destinations='.$lat2.','.$lon2.'&key='.env('GOOGLE_MAP_API_KEY'));
            $statusCode = $request->getStatusCode();
            $request->getHeaderLine('content-type');
            $response = json_decode($request->getBody()->getContents(), true);

            if ($statusCode == 200) {
                $data = $response;
            }
        } catch (Exception | GuzzleException $e) {
        }

        return $data;
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

if (! function_exists('phpDecrypt')) {

    /**
     * @param string $encrypted_string
     *
     * @return string
     * @Description decrypt string to simple string
     */
    function phpDecrypt(string $encrypted_string): string
    {
        // Store cipher method
        $ciphering = 'AES-256-CBC';
        // Use OpenSSl encryption method
        //	$iv_length = openssl_cipher_iv_length($ciphering);
        $options = 0;
        // Used random_bytes() which gives randomly
        // 16 digit values
        $decryption_iv = '@eatcard--kiosk@';
        // Store the decryption key
        $decryption_key = '!$@#eatcard_kiosk_device_encrypt#@$!';
        // Descrypt the string
        return openssl_decrypt(base64_decode($encrypted_string), $ciphering, $decryption_key, $options, $decryption_iv);
    }
}

if (! function_exists('sendAppNotificationHelper')) {
    /**
     * @param $order
     * @param $store
     *
     * @return array|void
     * @description send web notification to user after payment
     */
    function sendAppNotificationHelper($order, $store)
    {
        $name = '';
        if ($order['first_name'] || $order['last_name']) {
            $name = '| '.$order['first_name'].' '.$order['last_name'];
        }
        $desc_title = $store->store_name.' '.$name;
        $desc = $order['order_date'].' | '.$order['order_time'].' | €'.changePriceFormat($order['total_price']);
        $notification_data = [
            'type'              => 'takeaway',
            'description'       => $desc,
            'description_title' => $desc_title,
        ];
        $notification_data['additional_data'] = [
            'order_id'   => $order['id'],
            'order_date' => $order['order_date'],
        ];
        $notification_data['additional_data'] = json_encode($notification_data['additional_data']);
        try {
            $new_notification = GeneralNotification::create([
                'type'            => $notification_data['type'],
                'notification'    => $notification_data['description'],
                'additional_data' => $notification_data['additional_data'],
            ]);
            $user_ids = [];
            if ($store->store_manager) {
                $user_ids[] = $store->store_manager->user_id;
            }
            if ($store->store_owner) {
                $user_ids[] = $store->store_owner->user_id;
            }
            $one_signal_user_devices_ods = [];
            if ($new_notification && $user_ids) {
                $new_notification->users()->attach($user_ids);
                $devices = Device::query()->whereIn('user_id', $user_ids)->get();
                if (count($devices) > 0) {
                    $one_signal_user_devices_ods = $devices->pluck('onesignal_id')->toArray();
                }
                $push_notification_data = [
                    'title'             => 'Eatcard',
                    'text'              => $notification_data['description'],
                    'type'              => $notification_data['type'],
                    'description_title' => (! empty($notification_data['description_title'])) ? $notification_data['description_title'] : '',
                    'additional_data'   => $notification_data['additional_data'],
                    'player_ids'        => $one_signal_user_devices_ods,
                    'store_id'          => $order['store_id'],
                ];
                try {
                    $is_send_push = OneSignal::sendPushNotification($push_notification_data);
                    if ($is_send_push) {
                        $new_notification->users()->detach($user_ids);
                        $new_notification->delete();
                    }
                } catch (\Exception $exception) {
                    $takeaway_data['exception'] = json_encode($exception);

                    return $takeaway_data;
                }
            }
        } catch (\Exception $e) {
            companionLogger('reservation status change push notification error :', 'Error : '.$e->getMessage(), 'Line : '.$e->getLine(), 'File : '.$e->getFile(), 'IP address : '.request()->ip(), 'Browser : '.request()->header('User-Agent'));
        }
    }
}

if (! function_exists('set_discount_with_prifix')) {

    /**
     * @param $discount_type
     * @param $discount
     * @param int $only_return_sign
     *
     * @return string
     */
    function set_discount_with_prifix($discount_type, $discount, int $only_return_sign = 0)
    {
        $discount_with_prifix = '';
        $discount_prifix = $discount_type == 1 ? 'eur' : '%';
        if ($only_return_sign == 1) {
            $discount_with_prifix = ' ('.$discount_prifix.')';

            return $discount_with_prifix;
        }
        if ($discount > 0) {
            $discount_with_prifix = ' ('.changePriceFormat($discount).' '.$discount_prifix.')';
        }

        return $discount_with_prifix;
    }
}

if (! function_exists('extractRevenuePayload')) {

    /**
     * @param $payload
     *
     * @return mixed
     */
    function extractRevenuePayload($payload)
    {
        $date = '';
        $month = 0;
        $year = 0;
        $storeId = 0;
        $requestType = '';
        $generator = '';

        $validProtocolRequestType = [
            'daily', // Daily revenue print
            'monthly', // monthly revenue print
        ];

        if (isset($payload['date']) && ! empty($payload['date'])) {
            $date = $payload['date'];
        }

        if (isset($payload['month']) && ! empty($payload['month'])) {
            $month = $payload['month'];
        }

        if (isset($payload['year']) && ! empty($payload['year'])) {
            $year = $payload['year'];
        }

        if (isset($payload['store_id']) && ! empty($payload['store_id'])) {
            if (strpos($payload['store_id'], '?') !== false) {
                $payloadData = explode('?', $payload['store_id']);
                $storeId = $payloadData[0];
                $requestType = strpos($payloadData[1], 'daily') !== false ? 'daily' : (strpos($payloadData[1], 'monthly') !== false ? 'monthly' : $payloadData[1]);
            } else {
                $storeId = $payload['store_id'] ?? 0;
            }
        }

        if ($requestType == 'daily') {
            $generator = DailyRevenueGenerator::class;
        } elseif ($requestType == 'monthly') {
            $generator = MonthlyRevenueGenerator::class;
        } elseif (! in_array($requestType, $validProtocolRequestType)) {
            $requestType = '';
        }

        return [
            'generator' => $generator,
            'requestType' => $requestType,
            'storeId' => $storeId,
            'date' => $date,
            'month' => $month,
            'year' => $year,
        ];
    }
}

if (! function_exists('extractRequestType')) {

    /**
     * @param $requestType
     *
     * @return mixed
     */
    function extractRequestType($requestType)
    {
        $deviceId = 0;
        $generator = '';
        $printType = '';
        $systemPrintType = '';
        $systemType = SystemTypes::POS;

        $validProtocolRequestType = [
            'receipt', // Reprint only main receipt / save order
            'full_receipt', // Reprint all Main, Kitchen, Label
            'receipt_pos', // Print default / Proforma print
            'receipt_pos_order', // Reprint only main receipt
            'receipt_pos_sub', // Print sub order default
            'receipt_kiosk', // Print default for kiosk
            'receipt_kitchen', // Reprint only kitchen and label from admin for paid orders
        ];

        if (strpos($requestType, '@') !== false) {
            $pos_device_id = explode('@', $requestType);
            $deviceId = $pos_device_id[1];
            $requestType = $pos_device_id[0];
        }
        if ($requestType) {
            $checkReservation = explode('-', $requestType);
            if ($checkReservation[0] == 'res') {
                $generator = RunningOrderGenerator::class;
                $printType = 'reservation';
                $deviceId = $checkReservation[1];
                $requestType = $checkReservation[2];
                $systemPrintType = PrintTypes::PROFORMA;
            } elseif ($checkReservation[0] == 'saved_order') {
                $generator = SaveOrderGenerator::class;
                $requestType = isset($checkReservation[1]) ? $checkReservation[1] : 'receipt';
                $printType = 'saved_order';
                $systemPrintType = PrintTypes::DEFAULT;
            } elseif ($requestType == 'receipt_pos_sub') {
                $printType = 'receipt_pos_sub';
                $generator = SubOrderGenerator::class;
                $systemPrintType = PrintTypes::DEFAULT;
            } elseif (in_array($requestType, $validProtocolRequestType) && empty($generator)) {
                $generator = PaidOrderGenerator::class;
                $printType = 'order';
            } else {
                $generator = $requestType;
            }
        }

        if ($requestType == 'full_receipt') {
            $systemPrintType = PrintTypes::MAIN_KITCHEN_LABEL;
        } elseif ($requestType == 'receipt' && empty($systemPrintType)) {//Skip if SAVE ORDER print already set
            $systemPrintType = PrintTypes::MAIN;
        } elseif ($requestType == 'receipt_pos' && empty($systemPrintType)) { //Skip if PROFORMA print
            $systemPrintType = PrintTypes::DEFAULT;
        } elseif ($requestType == 'receipt_pos_order') {
            $systemPrintType = PrintTypes::MAIN;
        } elseif ($requestType == 'receipt_kitchen') {
            $systemType = SystemTypes::ADMIN;
            $systemPrintType = PrintTypes::KITCHEN_LABEL;
        } elseif ($requestType == 'receipt_kiosk') {
            $systemType = SystemTypes::KIOSK;
            $systemPrintType = PrintTypes::DEFAULT;
        }

        return [
            'generator' => $generator,
            'requestType' => $requestType,
            'deviceId' => $deviceId,
            'printType' => $printType,
            'printMethod' => PrintMethod::PROTOCOL,
            'systemPrintType' => $systemPrintType,
            'systemType' => $systemType,

        ];
    }
}
