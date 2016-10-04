<?php

namespace Zerifas\Supermodel\Console;

abstract class Config
{
    protected static $path = '';
    protected static $config = null;

    public static function setPath($path)
    {
        static::$path = $path;
        static::$config = null;
    }

    public static function get()
    {
        if (static::$config === null) {
            $configPath = static::$path . '/supermodel.json';

            if (! file_exists($configPath)) {
                throw new \Exception('No such file: ' . $configPath);
            }

            static::$config = json_decode(file_get_contents($configPath), true);
        }

        return static::$config;
    }
}
