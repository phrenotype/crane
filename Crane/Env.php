<?php

namespace Crane;

class Env
{
    public static function env($key)
    {
        return $_SERVER[$key] ?? $_ENV[$key] ?? getenv($key) ?? false;
    }

    public static function load($path)
    {
        $assoc = parse_ini_file($path, false, INI_SCANNER_RAW);
        if ($assoc) {
            foreach ($assoc as $k => $v) {
                $_SERVER[$k] = $v;
                $_ENV[$k] = $v;
                putenv("$k=$v");
            }
        }
    }
}
