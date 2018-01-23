<?php

namespace App\Responses;


use App\CacheTimes;

class BuycraftShowCoupon extends BuycraftDefaultResponse
{

    public function __construct(string $secret, string $id)
    {
        parent::__construct($secret, sprintf('coupons/%d', intval($id)), true, CacheTimes::BUYCRAFT_COUPON_LISTING());
    }

}