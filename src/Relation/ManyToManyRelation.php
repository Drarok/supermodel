<?php

namespace Zerifas\Supermodel\Relation;

class ManyToManyRelation extends AbstractRelation
{
    /**
     * @var string
     */
    protected $throughTable;

    /**
     * @var string
     */
    protected $nearJoinColumn;

    /**
     * @var string
     */
    protected $farJoinColumn;

    public function __construct(string $model, string $throughTable, string $nearJoinColumn, string $farJoinColumn)
    {
        parent::__construct($model, '', '', '');

        $this->throughTable = $throughTable;
        $this->nearJoinColumn = $nearJoinColumn;
        $this->farJoinColumn = $farJoinColumn;
    }

    public function getThroughTable(): string
    {
        return $this->throughTable;
    }

    public function getNearJoinColumn(): string
    {
        return $this->nearJoinColumn;
    }

    public function getFarJoinColumn(): string
    {
        return $this->farJoinColumn;
    }
}
