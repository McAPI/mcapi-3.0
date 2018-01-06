<?php

namespace App\Responses;


use App\CacheTimes;

class BuycraftPaymentsList extends BuycraftDefaultResponse
{

    public function __construct(string $secret)
    {
        parent::__construct($secret, 'payments', true, CacheTimes::BUYCRAFT_PAYMENTS_LIST());
    }

}