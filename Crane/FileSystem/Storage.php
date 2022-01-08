<?php

namespace Crane\FileSystem;

class Storage
{

    public static function root(): string
    {
        return $_SERVER["DOCUMENT_ROOT"] . '/';
    }

    public static function copy($src, $dest): bool
    {
        return copy($src, $dest);
    }

    public static function exists($path): bool
    {
        return file_exists($path);
    }

    public static function move($from, $to): bool
    {
        return rename($from, $to);
    }

    public static function delete($path): bool
    {
        return unlink($path);
    }

    public static function touch($path): bool
    {
        return touch($path);
    }

    public static function upload($src, $dest): bool
    {
        return move_uploaded_file($src, $dest);
    }
}
