<?php

namespace Zerifas\Supermodel\Test\Model;

use Zerifas\Supermodel\AutoAccessorsTrait;
use Zerifas\Supermodel\Model;

class TagModel extends Model
{
    use AutoAccessorsTrait;

    protected $name;

    public static function getTableName(): string
    {
        return 'tags';
    }

    public static function getColumns(): array
    {
        return [
            'id',
            'name',
        ];
    }

    public static function getValueTransformers(): array
    {
        return [];
    }

    public static function getRelations(): array
    {
        return [];
    }


}
