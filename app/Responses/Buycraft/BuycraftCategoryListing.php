<?php

namespace App\Responses;


class BuycraftCategoryListing extends BuycraftDefaultResponse
{

    public function __construct(string $secret)
    {
        parent::__construct($secret, 'listing', true, 10);
    }

}