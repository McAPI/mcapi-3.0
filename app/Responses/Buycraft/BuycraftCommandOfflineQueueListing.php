<?php

namespace App\Responses;


class BuycraftCommandOfflineQueueListing extends BuycraftDefaultResponse
{

    public function __construct(string $secret)
    {
        parent::__construct($secret, 'queue/offline-commands', true, 15);
    }

}