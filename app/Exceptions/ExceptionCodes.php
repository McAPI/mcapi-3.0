<?php
/**
 * Created by PhpStorm.
 * User: Yonas
 * Date: 02.01.2018
 * Time: 03:21
 */

namespace App\Exceptions;


class ExceptionCodes
{

    public static function INTERNAL_ILLEGAL_ARGUMENT_EXCEPTION() : int
    {
        return 1500;
    }

    public static function INTERNAL_ILLEGAL_STATE_EXCEPTION() : int
    {
        return 1501;
    }

    public static function INTERNAL_ILLEGAL_CACHE_ACCESS_EXCEPTION() : int
    {
        return 1502;
    }

    public static function INTERNAL_SERVER_IN_ILLEGAL_STATE_EXCEPTION() : int
    {
        return 1503;
    }

}