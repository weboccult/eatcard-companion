<?php

namespace Weboccult\EatcardCompanion\Helpers;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Weboccult\EatcardCompanion\Models\Order;
use Weboccult\EatcardCompanion\Models\OrderHistory;

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
     * @description Split and return splited or diff od splited value
     *
     * @param $val
     * @param $split_digit
     * @param $return_diff
     * @param $return_digit
     * @param $payment_digit
     *
     * @return $rounded_digit
     */
    function splitDigits($val, $split_digit = 4, $return_diff = false, $return_digit = 4, $payment_digit = 2)
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
        } catch (\Exception $exception) {
            Log::info('splitDigits function exception  Line : '.$exception->getLine().' | error : '.$exception->getMessage());

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
        $day = $day2let ? appDutchDay2Letter(Carbon::parse($date)->format('l')) : $dutchDayNames[Carbon::parse($date)
            ->format('l')];

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

if (! function_exists('generatePOSOrderId')) {
    /**
     * @param $store_id
     *
     * @return int|string
     */
    function generatePOSOrderId($store_id)
    {
        $order_history = OrderHistory::where('store_id', $store_id)->where('order_type', 'pos')->where(function ($q) {
            $q->where(DB::raw('LENGTH(order_id)'), '=', '8')->where('order_id', 'LIKE', Carbon::now()
                        ->format('y').'0%');
        })

                ->whereNotBetween('created_at', [
                '2021-03-11 00:00:00',
                '2021-03-12 23:59:59',
            ])->orderBy('created_at', 'desc')->first();
        if ($order_history) {
            $order_history->makeHidden(['full_name']);
        }
        $order = Order::where('store_id', $store_id)->where('order_type', 'pos')->where(function ($q) {
            $q->where(DB::raw('LENGTH(order_id)'), '=', '8')
                    ->where('order_id', 'LIKE', Carbon::now()->format('y').'0%');
        })
                //            ->whereDate('created_at',date('Y-m-d'))
                ->orderBy('created_at', 'desc')->first();

        if ($order) {
            $order->makeHidden(['full_name']);
        }
        if ($order && $order_history) {
            //            Log::info('order & order history '.$order_history->id);
            if ($order_history->id > $order->id) {
                //                Log::info(' order history > order '. $order->id.' '.$order_history->id);
                $order = $order_history;
            }
        } elseif ($order_history) {
            //            Log::info('only order history '.$order_history->id);
            $order = $order_history;
        }
        //        Log::info('new order id + 1 '.$order->order_id);
        return $order ? $order->order_id + 1 : date('y').'000001';
    }
}
