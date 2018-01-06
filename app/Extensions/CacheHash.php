<?php

namespace App\Extensions;

class CacheHash
{

    private function __construct()
    {
        //
    }

    public static function make($value)
    {
        //TODO SECURITY Deserves more thought than this.

        return hash('SHA512', $value);
    }

}