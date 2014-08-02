<?php

namespace Zerifas\Supermodel\Transformer;

abstract class Boolean
{
    /**
     * Transform an integer to a bool.
     *
     * @param int $int Integer, where 0 is false, all other values are true.
     *
     * @return bool
     */
    public static function fromInteger($integer)
    {
        if ($integer === null) {
            return null;
        }

        return ($integer !== 0);
    }

    /**
     * Transform a bool to an integer.
     *
     * @param bool $bool Boolean value.
     *
     * @return int
     */
    public static function fromBoolean($bool = null)
    {
        if ($bool === null) {
            return null;
        }

        return $bool ? 1 : 0;
    }
}
