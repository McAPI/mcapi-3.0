<?php

namespace App\Responses;


use App\CacheTimes;

class BuycraftCommandOnlineQueueListing extends BuycraftDefaultResponse
{

    public function __construct(string $secret, string $playerID)
    {
        parent::__construct($secret, sprintf('queue/online-commands/%d', intval($playerID)), true, CacheTimes::BUYCRAFT_COMMAND_QUEUE_LISTING());
    }


}