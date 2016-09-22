<?php

namespace Zerifas\Supermodel\Transformer;

abstract class Boolean implements TransformerInterface
{
    /**
     * Transform an integer to a bool.
     *
     * @param int $int Integer, where 0 is false, all other values are true.
     *
     * @return bool
     */
    public static function fromArray($value)
    {
        if ($value === null) {
            return null;
        }

        return ($value !== 0);
    }

    /**
     * Transform a bool to an integer.
     *
     * @param bool $bool Boolean value.
     *
     * @return int
     */
    public static function toArray($value)
    {
        if ($value === null) {
            return null;
        }

        return $value !== false ? 1 : 0;
    }
}
