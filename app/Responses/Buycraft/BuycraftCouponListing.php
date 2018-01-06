<?php

namespace App\Responses;


use App\CacheTimes;

class BuycraftCouponListing extends BuycraftDefaultResponse
{

    public function __construct(string $secret)
    {
        parent::__construct($secret, 'coupons', true, CacheTimes::BUYCRAFT_COUPON_LISTING());
    }

}