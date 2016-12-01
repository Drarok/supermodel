<?php

namespace Zerifas\Supermodel\Relation;

class BelongsToRelation
{
    protected $joinModel;
    protected $joinColumn;
    protected $localColumn;

    public function __construct(string $joinModel, string $joinColumn, string $localColumn)
    {
        $this->joinModel = $joinModel;
        $this->joinColumn = $joinColumn;
        $this->localColumn = $localColumn;
    }

    public function getJoinModel(): string
    {
        return $this->joinModel;
    }

    public function getJoinColumn(): string
    {
        return $this->joinColumn;
    }

    public function getLocalColumn(): string
    {
        return $this->localColumn;
    }
}
