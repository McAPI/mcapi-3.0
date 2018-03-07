<?php

namespace App\Responses\User;


class IdentifierTypes
{

    //---
    //@link https://stackoverflow.com/a/19989922/8555061
    private static $_REGEX_UUIDv4 = '/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/';
    private static $_REGEX_UUIDv4_MOJANG = '/^[0-9A-F]{8}[0-9A-F]{4}4[0-9A-F]{3}[89AB][0-9A-F]{3}[0-9A-F]{12}$/';
    //@link https://help.mojang.com/customer/en/portal/articles/928638-minecraft-usernames
    private static $_REGEX_USERNAME = '/^[a-zA-Z0-9_]{3,16}$/';

    private function __construct()
    {
    }

    public static function INVALID(): int
    {
        return -1;
    }

    public static function UUIDv4(): int
    {
        return 2;
    }

    public static function USERNAME(): int
    {
        return 3;
    }

    public static function fromIdentifier($identifier): int
    {
        if (!($identifier === null) && (
            preg_match(self::$_REGEX_UUIDv4, strtoupper($identifier)) === 1 ||
            preg_match(self::$_REGEX_UUIDv4_MOJANG, strtoupper($identifier)) === 1)
        ) {
            return self::UUIDv4();
        } elseif (!($identifier === null) && preg_match(self::$_REGEX_USERNAME, $identifier) === 1) {
            return self::USERNAME();
        } else {
            return self::INVALID();
        }
    }

    public static function cleanUUID($identifier): string
    {
        return str_replace('-', '', $identifier);
    }

}