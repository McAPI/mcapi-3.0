<?php

namespace App\Responses;


class BuycraftListing extends BuycraftDefaultResponse
{

    public function __construct(string $secret)
    {
        parent::__construct($secret, 'listing', true, 10);
    }

}