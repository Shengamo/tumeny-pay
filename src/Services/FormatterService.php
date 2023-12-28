<?php

namespace Shengamo\TumenyPay\Services;

class FormatterService
{
    /**
     * convertToK convert 1000 to K

     */
    public static function convertToK(int $value): int|string
    {
        if ($value < 1000) {
            return $value;
        }
        $suffix = ['','k','M','G','T','P','E','Z','Y'];
        $power = floor(log($value, 1000));
        return round($value/(1000**$power), 1, PHP_ROUND_HALF_EVEN).$suffix[$power];
    }

    public static function ngweeToKwacha($value): string
    {
        return number_format(($value /100), 2, '.', '');
    }

    public static function kwachaToNgwee($value): string
    {
        return strval(str_replace(',', '', $value) * 100);
    }
}
