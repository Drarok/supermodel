<?php

namespace Zerifas\Supermodel\Test\Model;

class InvalidModel extends PostModel
{
    public static function getRelations(): array
    {
        return [
            'invalid' => new class {
            },
        ];
    }
}
