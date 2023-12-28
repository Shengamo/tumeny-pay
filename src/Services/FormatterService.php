<?php

namespace Shengamo\TumenyPay\Services;

class FormatterService
{
    function __construct()
    {
    }
    /**
     * convertToK convert 1000 to K
     *
     * @param  mixed $n
     * @return void
     */
    public static function convertToK($n)
    {
        if ($n < 1000) {
            return $n;
        }
        $suffix = ['','k','M','G','T','P','E','Z','Y'];
        $power = floor(log($n, 1000));
        return round($n/(1000**$power), 1, PHP_ROUND_HALF_EVEN).$suffix[$power];
    }

    public static function ngweeToKwacha($value)
    {
        $converted = number_format(($value /100), 2, '.', '');
        return $converted;
    }

    public static function kwachaToNgwee($value)
    {
        $converted = strval(str_replace(',', '', $value) * 100);
        return $converted;
    }
}
