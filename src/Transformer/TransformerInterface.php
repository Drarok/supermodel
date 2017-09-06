<?php

namespace Zerifas\Supermodel\Transformer;

interface TransformerInterface
{
    public static function fromArray($value);
    public static function toArray($value);
}
