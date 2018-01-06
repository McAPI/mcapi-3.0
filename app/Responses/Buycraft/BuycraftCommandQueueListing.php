<?php

namespace App\Responses;


class BuycraftCommandQueueListing extends BuycraftDefaultResponse
{

    public function __construct(string $secret)
    {
        parent::__construct($secret, 'queue', true, 15);
    }

}