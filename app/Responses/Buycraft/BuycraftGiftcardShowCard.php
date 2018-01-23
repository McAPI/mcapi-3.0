<?php

namespace App\Responses;


use App\CacheTimes;

class BuycraftGiftcardShowCard extends BuycraftDefaultResponse
{

    public function __construct(string $secret, string $id)
    {
        parent::__construct($secret, sprintf('gift-cards/%d', intval($id)), true, CacheTimes::BUYCRAFT_COUPON_LISTING());
    }

}