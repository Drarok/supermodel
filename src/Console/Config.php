<?php

namespace Zerifas\Supermodel\Console;

use Zerifas\JSON;

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

            $validator = static::getConfigValidator();
            if (! $validator->isValid(file_get_contents($configPath))) {
                throw new \Exception('Invalid config: ' . implode(' ', $validator->getErrors()));
            }

            static::$config = $validator->getDocument();
        }

        return static::$config;
    }

    protected static function getConfigValidator()
    {
        $schema = new JSON\Object([
            'db'     => new JSON\Object([
                'host'     => new JSON\Str(),
                'dbname'   => new JSON\Str(),
                'charset'  => new JSON\OptionalStr('utf8'),
                'username' => new JSON\Str(),
                'password' => new JSON\Str(),
            ]),
            'models' => new JSON\Object([
                'namespace' => new JSON\Str(),
            ]),
        ]);

        return new JSON\Validator($schema);
    }
}
