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

    public final static function ACCEPTED() : int
    {
        return 202;
    }


    public final static function NO_CONTENT() : int
    {
        return 204;
    }

    public final static function ERROR_INTERNAL_SERVER_ERROR() : int
    {
        return 500;
    }

    public final static function ERROR_CLIENT_BAD_REQUEST() : int
    {
        return 400;
    }

    public final static function ERROR_CLIENT_FORBIDDEN() : int
    {
        return 403;
    }

    public final static function ERROR_TOO_MANY_REQUESTS() : int
    {
        return 429;
    }

    public final static function toString($code) : string
    {

        switch ($code) {

            case 200: return "Ok";
            case 202: return "Accepted";
            case 204: return "No Content";
            case 400: return "Client - Bad Request";
            case 500: return "Server - Internal Error";

            default: return "Internal Error: Unknown Error Code - Please report this on GitHub.";

        }

    }

}