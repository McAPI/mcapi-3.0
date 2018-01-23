<?php

namespace App\Responses;


use App\CacheTimes;

class BuycraftGiftcardListing extends BuycraftDefaultResponse
{

    public function __construct(string $secret)
    {
        parent::__construct($secret, 'gift-cards', true, CacheTimes::BUYCRAFT_COUPON_LISTING());
    }

}