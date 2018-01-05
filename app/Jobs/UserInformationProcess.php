<?php

namespace App\Jobs;

use App\Responses\User\UserInformation;
use App\Status;

class UserInformationProcess extends Job
{

    private $request;
    private $information;

    public function __construct(array $request, UserInformation $information)
    {
        $this->request = $request;
        $this->information = $information;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

        if($this->information->isCached()) {
            $this->delete();
            return;
        }

        $this->information->fetch($this->request, true);
        $this->delete();
    }

}
