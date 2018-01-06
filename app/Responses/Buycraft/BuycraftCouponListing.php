<?php

namespace App\Responses;


class BuycraftCouponListing extends BuycraftDefaultResponse
{

    public function __construct(string $secret)
    {
        parent::__construct($secret, 'coupons', true, 10);
    }

}