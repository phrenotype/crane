<?php

namespace Crane\Template;

class TemplateUtils
{
    public static function regexNotWithinQuotes(string $regex)
    {
        return '#(' . $regex . ')(?=[^\'"]*(?:[\'"][^\'"]*[\'"][^\'"]*)*$)#';
    }
    public static function pluck(string $property, $object)
    {
        if (is_object($object)) {
            return (isset($object->$property) ? $object->$property : $object->$property());
        } else if ((is_array($object) || $object instanceof \ArrayAccess) && ($object[$property] ?? false)) {
            return $object[$property];
        }
        return false;
    }
    public static function isPluckable($object)
    {
        if (is_object($object) || is_array($object) || $object instanceof \ArrayAccess) {
            return true;
        }
        return false;
    }
    public static function isPath($key)
    {
        return preg_match("#^(\w+\.)+\w+$#", $key) != false;
    }
    public static function extractPath($path, $object)
    {
        $pieces = explode('.', $path);
        $count = count($pieces);
        if ($count === 1) {
            return self::pluck($pieces[0], $object);
        } else if ($count > 1) {
            $cp = $pieces[0];
            unset($pieces[0]);
            $new_object = self::pluck($cp, $object);
            $new_path = join('.', $pieces);
            return self::extractPath($new_path, $new_object);
        }
        return false;
    }

    public static function replacePairs(string $content, array $pairs)
    {

        $replaced = $content;

        foreach ($pairs as $k => $v) {
            $value = '';
            if (!self::isPluckable($v) && !self::isPath($k) && !is_callable($v) && !is_object($v) && !($v instanceof \Closure)) {
                $value = $v;
            } else if (self::isPluckable($v) && !self::isPath($k) && is_object($v) && !($v instanceof \Closure)) {
                $value = $v->__toString();
            } else if (is_callable($v)) {

                $value = $v();
            }
            $replaced = preg_replace('#\{\{\s*' . $k . '\s*\}\}#', $value, $replaced);
        }

        return $replaced;
    }
    public static function replacePaths(string $content, array $context)
    {
        preg_match_all("{{\s*(?<path>(\w+\.)+\w+?)\s*}}", $content, $matches);

        $paths = $matches['path'] ?? [];

        $replaced = $content;

        foreach ($paths as $path) {

            if (self::isPath($path)) {

                $pieces = explode('.', $path);
                $object_name = $pieces[0];

                if (in_array($object_name, array_keys($context)) && self::isPluckable($context[$object_name])) {
                    unset($pieces[0]);
                    $object = $context[$object_name];
                    $value = self::extractPath(join('.', $pieces), $object);
                    $replaced = preg_replace('#\{\{\s*' . $path . '\s*\}\}#', $value, $replaced);
                }
            } else {
                $replaced = preg_replace('#\{\{\s*' . $path . '\s*\}\}#', 'Error occurred for : ' . $path, $replaced);
            }
        }
        return $replaced;
    }
    public static function includeExtended(string $content)
    {

        $child_file_contents = $content;

        $extends_regex = "(?s)\{%\s*extends\s*?([^\s]+?)\s*%}";

        preg_match("#$extends_regex#", $child_file_contents, $matches);

        if (!$matches) {
            return $child_file_contents;
        }

        $base_file_path = $matches[1] ?? '';

        /*  The line below is customized */
        if (!file_exists('views/' . $base_file_path)) {
            throw new \Error(sprintf("'views/%s' does not exist.", $base_file_path));
        }
        $parent_file_contents = file_get_contents('views/' . $base_file_path);

        $block_regex = "(?s)\{%\s*block\s*?(\w+)\s*%}";
        preg_match_all("#$block_regex#", $parent_file_contents, $matches);

        $all_blocks = $matches[1] ?? [];


        $full_block_regex = function ($block) {
            return "(?s)\{%\s*block\s*$block\s*%}"
                . "(.*?)?\{%\s*endblock\s*%}";
        };


        $block_content = function ($block) use ($child_file_contents, $full_block_regex) {

            $regex = $full_block_regex($block);
            preg_match("#$regex#", $child_file_contents, $matches);

            return $matches[1] ?? '';
        };


        $final_base_content = $parent_file_contents;

        foreach ($all_blocks as $block) {


            $derived = $block_content($block);

            $regex = $full_block_regex($block);
            $final_base_content = preg_replace(
                "#$regex#",
                $derived,
                $final_base_content
            );
        }


        return $final_base_content;
    }
    public static function includeFiles(string $content)
    {
        $include_block_regex = "(?s)\{%\s*include\s+?([^\s]+?)\s*%}";

        $block_regex = function ($block) {
            return "(?s)\{%\s*include\s+?$block\s*%}";
        };

        preg_match_all("#$include_block_regex#", $content, $matches);
        $files = $matches[1] ?? [];

        $final_content = $content;

        foreach ($files as $file) {

            /* Customized the line below */
            if (!file_exists('views/' . $file)) {
                throw new \Error(sprintf("'%s' does not exist.", $file));
            }
            $other_file = file_get_contents('views/' . $file);

            $regex = $block_regex($file);
            $final_content = preg_replace(
                "#$regex#",
                $other_file,
                $final_content
            );
        }

        return $final_content;
    }
}
