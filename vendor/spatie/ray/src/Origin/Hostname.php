<?php

namespace Spatie\Ray\Origin;

class Hostname
{
    protected static $hostname = null;

    public static function get(): string
    {
        return static::$hostname ?? gethostname();
    }

    public static function set(string $hostname)
    {
        static::$hostname = $hostname;
    }
}
