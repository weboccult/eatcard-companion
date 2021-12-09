<?php

use function Weboccult\EatcardCompanion\Helpers\{splitDigits};

it('should split 4 digit from decimal point.', function () {
    $value = 19.999966;
    expect(splitDigits($value))->toEqual(19.9999);
});

it('should split 2 digit from decimal point.', function () {
    $value = 19.9966;
    expect(splitDigits($value, 2))->toEqual(19.99);
});

it('should split 3 digit from decimal point and return difference. (payment digit = 2)', function () {
    $value = 19.9996;
    expect(splitDigits($value, 3, true))->toEqual(-0.009);
});

it('should split 4 digit from decimal point and return difference. (payment digit = 3)', function () {
    $value = 19.9996;
    expect(splitDigits($value, 4, true, 4, 3))->toEqual(-0.0006);
});
