<?php

namespace Zerifas\Supermodel;

use Zerifas\Supermodel\Relation\AbstractRelation;
use Zerifas\Supermodel\Transformers\TransformerInterface;

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
     * Get relations, keyed on name
     *
     * @return AbstractRelation[]
     */
    public static function getRelations(): array;

    /**
     * Get value transformers, keyed on column name
     *
     * @return TransformerInterface[]
     */
    public static function getValueTransformers(): array;
}
