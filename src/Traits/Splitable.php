<?php

namespace Weboccult\EatcardCompanion\Traits;

trait Splitable
{
    function splitDigits($val,$split_digit = 4,$return_diff = false,$return_digit = 4,$payment_digit = 2)
    {
        $round_return = 0;
        $round_diff = 0;
        $rounded_digit = floatval(bcdiv($val,1,$split_digit));
        if ($return_diff){
            $payment_amount = floatval(bcdiv($val,1,$payment_digit));
            $round_return = floatval(bcdiv($val,1,$split_digit));
	        return round($payment_amount - $round_return,$return_digit);
    	}
    	return $rounded_digit;
    }
}
