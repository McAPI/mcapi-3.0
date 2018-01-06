<?php

namespace App\Responses;


use App\CacheTimes;

class BuycraftCategoryListing extends BuycraftDefaultResponse
{

    public function __construct(string $secret)
    {
        parent::__construct($secret, 'listing', true, CacheTimes::BUYCRAFT_CATEGORY_LISTING());
    }

}