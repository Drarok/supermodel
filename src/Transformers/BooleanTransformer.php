<?php

namespace Zerifas\Supermodel\Transformers;

class BooleanTransformer implements TransformerInterface
{
    /**
     * @param int $value
     *
     * @return bool
     */
    public static function fromArray($value)
    {
        return $value === 1;
    }

    /**
     * @param bool $value
     *
     * @return int
     */
    public static function toArray($value)
    {
        return $value ? 1 : 0;
    }
}
