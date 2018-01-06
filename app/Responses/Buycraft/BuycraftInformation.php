<?php

namespace App\Responses;

class BuycraftInformation extends BuycraftDefaultResponse
{

    public function __construct(string $secret)
    {
        parent::__construct($secret, 'information', true, 5);
    }

}