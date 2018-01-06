<?php

namespace App\Responses;

use App\CacheTimes;

class BuycraftInformation extends BuycraftDefaultResponse
{

    public function __construct(string $secret)
    {
        parent::__construct($secret, 'information', true, CacheTimes::BUYCRAFT_INFORMATION());
    }

}