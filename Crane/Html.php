<?php

namespace Crane;

class Html
{

    public static function encode($value)
    {
        return htmlentities(trim($value), ENT_QUOTES, "UTF-8", false);
    }

    public static function clean(array $data)
    {
        if (is_array($data)) {
            foreach ($data as $k => $v) {
                (is_array($v)) && ($data[$k] = self::clean($v));
                (!is_array($v)) && ($data[$k] = self::encode($v));
            }
        } else {
            $data = self::encode($data);
        }
        return $data;
    }
}
