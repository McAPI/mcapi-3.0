<?php

namespace App\Responses;


use App\CacheTimes;

class BuycraftBanListing extends BuycraftDefaultResponse
{

    public function __construct(string $secret)
    {
        parent::__construct($secret, 'bans', true, CacheTimes::BUYCRAFT_BAN_LISTING());
    }

}