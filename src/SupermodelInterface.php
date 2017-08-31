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
     * Get relations
     *
     * @return AbstractRelation[]
     */
    public static function getRelations(): array;

    /**
     * Get value transformers
     *
     * @return TransformerInterface[]
     */
    public static function getValueTransformers(): array;
}
