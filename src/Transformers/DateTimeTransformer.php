<?php

namespace Zerifas\Supermodel\Transformers;

use DateTime;

class DateTimeTransformer implements TransformerInterface
{
    const FORMAT = 'Y-m-d H:i:s';

    /**
     * @param string $value
     *
     * @return DateTime
     */
    public static function fromArray($value)
    {
        return DateTime::createFromFormat(static::FORMAT, $value);
    }

    /**
     * @param DateTime $value
     *
     * @return string
     */
    public static function toArray($value)
    {
        return $value->format(static::FORMAT);
    }
}
