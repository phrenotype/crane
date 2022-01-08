<?php

namespace Crane\FileSystem;

class Module
{
    public static function import($folder)
    {
        global $app;
        $files = array_diff(scandir($folder), ['.', '..']);
        $files = array_filter($files, function ($f) {
            if (preg_match("/^\./", $f)) {
                return false;
            } else {
                return true;
            }
        });
        foreach ($files as $file) {
            $file = $folder . DIRECTORY_SEPARATOR . $file;
            (is_dir($file)) && (self::import($file)) || (require($file));
        }
        return true;
    }
}
