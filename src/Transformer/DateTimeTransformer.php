<?php

namespace Zerifas\Supermodel\Transformer;

class DateTimeTransformer implements TransformerInterface
{
    private const string FORMAT = 'Y-m-d H:i:s';

    public static function fromArray($value)
    {
        return \DateTimeImmutable::createFromFormat('!' . static::FORMAT, $value);
    }

    public static function toArray($value)
    {
        return $value->format(static::FORMAT);
    }
}
