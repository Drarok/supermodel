<?php

namespace Zerifas\Supermodel\Test;

class PDOStatement extends \PDOStatement
{
    protected static $defaultIsExhausted = false;

    protected $isExhausted;

    public static function setDefaultIsExhausted($value)
    {
        self::$defaultIsExhausted = (bool) $value;
    }

    public function fetch($how = null, $orientation = null, $offset = null)
    {
        if ($this->isExhausted === null) {
            $this->isExhausted = self::$defaultIsExhausted;
        }

        if ($this->isExhausted) {
            return false;
        }

        $this->isExhausted = true;

        return [
            'fake:id'        => 1,
            'fake:createdAt' => '2016-01-01 00:00:00',
            'fake:updatedAt' => '2016-01-01 00:00:00',
            'fake:enabled'   => 1,
        ];
    }
}
