<?php

namespace ImdbDataFiles;

class Tools
{
    public static function string($string, $nullable = true)
    {
        return ($string || !$nullable) ? "'".static::mres($string)."'" : 'NULL';
    }

    public static function mres($string)
    {
        $search = ["\\", "\x00", "\n", "\r", "'", '"', "\x1a"];
        $replace = ["\\\\", "\\0", "\\n", "\\r", "''", '\"', "\\Z"];

        return str_replace($search, $replace, $string);
    }
}