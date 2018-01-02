<?php

namespace App\Jobs;

use App\Responses\User\UserInformation;
use Illuminate\Support\Facades\Cache;

class UserInformationProcess extends Job
{

    private $information;

    public function __construct(UserInformation $information)
    {
        //$this->information = $information;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Cache::permanent($this->information->getPermanentCacheKey(), 'Test');
        Cache::set($this->information->getCacheKey(), 'Test', 10);

    }

}
