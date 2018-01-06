<?php

namespace App\Responses;


use App\CacheTimes;

class BuycraftCommandQueueListing extends BuycraftDefaultResponse
{

    public function __construct(string $secret)
    {
        parent::__construct($secret, 'queue', true, CacheTimes::BUYCRAFT_COMMAND_QUEUE_LISTING());
    }

}