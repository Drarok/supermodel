<?php

namespace Zerifas\Supermodel;

interface SupermodelInterface
{
    /**
     * Get the table name
     *
     * @return string
     */
    public static function getTableName(): string;

    /**
     * Get columns
     *
     * @return string[]
     */
    public static function getColumns(): array;

    /**
     * Get value transformers
     *
     * @return array
     */
    public static function getValueTransformers(): array;

    /**
     * Get relations
     *
     * @return ColumnReference[]
     */
    public static function getRelations(): array;
}
