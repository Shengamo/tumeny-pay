<?php

namespace Shengamo\TumenyPay;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Shengamo\TumenyPay\Skeleton\SkeletonClass
 */
class TumenyPayFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'tumeny-pay';
    }
}
