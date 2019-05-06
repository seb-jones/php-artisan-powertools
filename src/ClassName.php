<?php

namespace PhpArtisanPowertools;
use Illuminate\Support\Str;

class ClassName
{
    /**
     * Returns the absolute path of the given class name.
     */
    public static function absolute(string $class)
    {
        $class = collect(explode('\\', trim($class)))->map(function ($string) {
            return Str::studly($string);
        })->implode('\\');

        if (Str::startsWith($class, '\\')) {
            return $class;
        }
        else {
            return "\\App\\$class";
        }
    }

    /**
     * Returns the class name of the given absolute class name.
     */
    public static function class(string $class)
    {
        return collect(explode('\\', trim($class)))->last();
    }
}
