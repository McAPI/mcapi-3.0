<?php

namespace App;


use App\Jobs\Job;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Bus;
use Pheanstalk\Exception\ConnectionException;

/**
 * Class McAPIQueue
 * @package App
 *
 * This class allows access to the Queue without breaking the application in the case of it not being available.
 */
class McAPIQueue
{

    private static $isAvailable = true;

    public static function boot()
    {
        try {
            Queue::connection()->getPheanstalk()->stats();
            self::$isAvailable = true;
        }catch (ConnectionException $exception) {
            self::$isAvailable = false;
        }
    }

    public static function isAvailable() : bool
    {
        return self::$isAvailable;
    }

    public static function dispatch(Job $job) : bool
    {

        if(self::isAvailable()) {
            Bus::dispatch($job);
            return true;
        }

        return false;

    }

}