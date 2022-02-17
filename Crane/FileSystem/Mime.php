<?php

namespace Crane\FileSystem;

class Mime
{
    public static function mime(string $filepath)
    {
        $fn = new \finfo(FILEINFO_MIME);
        $mime = $fn->file($filepath);
        preg_match("/^[^;]+/", $mime, $part);
        $mime = $part[0] ?? null;
        return $mime;
    }
}
