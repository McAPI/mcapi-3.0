<?php

namespace App;

/**
 * Class Status
 *
 * We follow the HTTP response code conventions. @link https://tools.ietf.org/html/rfc7231
 * This class is used to represent HTTP status codes & the internal health status of the application.
 *
 * @package App
 */
class Status
{

    public final static function OK() : int
    {
        return 200;
    }

    public final static function ERROR_INTERNAL_SERVER_ERROR() : int
    {
        return 500;
    }

    public final static function ERROR_CLIENT_BAD_REQUEST() : int
    {
        return 400;
    }

    public final static function toString($code) : string
    {

        switch ($code) {

            case 200: return "Ok";
            case 400: return "Client - Bad Request";
            case 500: return "Server - Internal Error";

            default: return "Unknown Error Code - Please report this on GitHub.";

        }

    }

}