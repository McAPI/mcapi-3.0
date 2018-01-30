<?php

namespace App;


use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Predis\Connection\ConnectionException;

/**
 * Class McAPICache
 * @package App
 *
 * This class allows access to the Cache without breaking the application in the case of it not being available.
 */
class McAPICache
{

    private static $isAvailable = false;

    public static function boot()
    {
        try {
            Redis::ping();
            self::$isAvailable = true;
        }catch (ConnectionException $exception) {
            self::$isAvailable = false;
        }
    }

    /**
     * @return bool
     */
    public static function isAvailable(): bool
    {
        return self::$isAvailable;
    }

    public static function has(string $key) : bool
    {

        if(self::isAvailable()) {
            return Cache::has($key);
        }

        return false;

    }

    public static function get(string $key)
    {

        if(self::isAvailable()) {
            return Cache::get($key);
        }

        return null;

    }

    public static function put(string $key, $value, $minutes) : bool
    {

        if(self::isAvailable()) {
            Cache::put($key, $value, $minutes);
            return true;
        }

        return false;

    }

    public static function forver(string $key, $value) : bool
    {

        if(self::isAvailable()) {
            Cache::forever($key, $value);
            return true;
        }

        return false;

    }

}