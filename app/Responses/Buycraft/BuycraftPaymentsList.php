<?php

namespace App\Responses;


class BuycraftPaymentsList extends BuycraftDefaultResponse
{

    public function __construct(string $secret)
    {
        parent::__construct($secret, 'payments', true, 1);
    }

}