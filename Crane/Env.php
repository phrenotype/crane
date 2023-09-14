<?php

namespace Crane;

class Env
{
    private static $config = [];

    public static function env($key)
    {
        return $config[$key] ?? null;
    }

    public static function load($path)
    {
        $config = require($path);
        static::$config = $config;
    }
}
