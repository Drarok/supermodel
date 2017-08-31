<?php

namespace Zerifas\Supermodel\Relation;

class HasManyRelation extends AbstractRelation
{
    public function __construct(string $model, $foreignColumn, string $localColumn = 'id')
    {
        parent::__construct($model, $localColumn, $foreignColumn);
    }
}
