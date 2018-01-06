<?php

namespace App\Responses;


use App\CacheTimes;

class BuycraftCommandOfflineQueueListing extends BuycraftDefaultResponse
{

    public function __construct(string $secret)
    {
        parent::__construct($secret, 'queue/offline-commands', true, CacheTimes::BUYCRAFT_COMMAND_OFFLINE_QUEUE_LISTING());
    }

}