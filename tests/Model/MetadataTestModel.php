<?php

namespace Zerifas\Supermodel\Test\Model;

use Zerifas\Supermodel\SupermodelInterface;

class MetadataTestModel implements SupermodelInterface
{
    private static $tableName = 0;
    private static $columns = 0;
    private static $valueTransformers = 0;
    private static $relations = 0;

    public static function getTableName(): string
    {
        ++self::$tableName;
        return 'posts';
    }

    public static function getTableNameCount(): int
    {
        return self::$tableName;
    }

    public static function getColumns(): array
    {
        ++self::$columns;
        return [
            'id',
            'name',
        ];
    }

    public static function getColumnsCount(): int
    {
        return self::$columns;
    }

    public static function getValueTransformers(): array
    {
        ++self::$valueTransformers;
        return [];
    }

    public static function getValueTransformersCount(): int
    {
        return self::$valueTransformers;
    }

    public static function getRelations(): array
    {
        ++self::$relations;
        return [];
    }

    public static function getRelationsCount(): int
    {
        return self::$relations;
    }
}
