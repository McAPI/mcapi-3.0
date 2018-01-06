<?php

namespace App\Responses;


class BuycraftBanListing extends BuycraftDefaultResponse
{

    public function __construct(string $secret)
    {
        parent::__construct($secret, 'bans', true, 10);
    }

}