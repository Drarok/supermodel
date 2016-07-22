<?php

namespace Zerifas\Supermodel\Transformer;

abstract class AbstractDate implements TransformerInterface
{
    const FORMAT_STRING = '';

    public static function fromArray($value)
    {
        if ($value === null) {
            return null;
        }

        return \DateTime::createFromFormat(static::FORMAT_STRING, $value);
    }

    public static function toArray($value)
    {
        if ($value === null) {
            return null;
        }

        return $value->format(static::FORMAT_STRING);
    }
}
