<?php

namespace App;


class CacheTimes
{

    private function __construct()
    {
        //
    }

    public static function BUYCRAFT_BAN_LISTING() : int
    {
        return 10;
    }

    public static function BUYCRAFT_CATEGORY_LISTING() : int
    {
        return 10;
    }

    public static function BUYCRAFT_COMMAND_OFFLINE_QUEUE_LISTING() : int
    {
        return 10;
    }

    public static function BUYCRAFT_COMMAND_QUEUE_LISTING() : int
    {
        return 10;
    }

    public static function BUYCRAFT_COUPON_LISTING() : int
    {
        return 10;
    }

    public static function BUYCRAFT_INFORMATION() : int
    {
        return 15;
    }

    public static function BUYCRAFT_PAYMENTS_LIST() : int
    {
        return 1;
    }

    public static function GAME_VERSIONS() : int
    {
        return (60 * 24);
    }

    public static function GAME_SERVICE_STATUS() : int
    {
        return 5;
    }

    public static function SERVER_PING() : int
    {
        return 10;
    }

    public static function USER_INFORMATION() : int
    {
        return 30;
    }

    public static function USER_REPUTATION() : int
    {
        return (60 * 24);
    }

}