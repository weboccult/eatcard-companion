<?php

namespace Weboccult\EatcardCompanion\Helpers;

use Illuminate\Support\Facades\Log;

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
