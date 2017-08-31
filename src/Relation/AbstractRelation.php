<?php

namespace Zerifas\Supermodel\Relation;

abstract class AbstractRelation
{
    /**
     * @var string
     */
    protected $model;

    /**
     * @var string
     */
    protected $localColumn;

    /**
     * @var string
     */
    protected $foreignColumn;

    public function __construct(string $model, string $localColumn, string $foreignColumn = 'id')
    {
        $this->model = $model;
        $this->localColumn = $localColumn;
        $this->foreignColumn = $foreignColumn;
    }

    public function getModel(): string
    {
        return $this->model;
    }

    public function getLocalColumn(): string
    {
        return $this->localColumn;
    }

    public function getForeignColumn(): string
    {
        return $this->foreignColumn;
    }
}
