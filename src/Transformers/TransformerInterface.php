<?php

namespace Zerifas\Supermodel\Transformers;

interface TransformerInterface
{
    public static function fromArray($value);
    public static function toArray($value);
}
