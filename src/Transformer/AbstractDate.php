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

        if (! is_object($value) || ! ($value instanceof \DateTime)) {
            throw new \InvalidArgumentException(sprintf(
                'Invalid value passed to %s::toArray',
                static::class
            ));
        }

        return $value->format(static::FORMAT_STRING);
    }
}
