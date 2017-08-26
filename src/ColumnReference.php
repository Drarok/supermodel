<?php

namespace Zerifas\Supermodel;

class ColumnReference
{
    const OPERATOR_EQUAL = '=';
    const OPERATOR_NOT_EQUAL = '!=';
    const OPERATOR_LESS = '<';
    const OPERATOR_GREATER = '>';
    const OPERATOR_LESS_OR_EQUAL = '<=';
    const OPERATOR_GREATER_OR_EQUAL = '>=';

    const OPERATOR_LIKE = 'LIKE';
    const OPERATOR_IS_NULL = 'IS NULL';
    const OPERATOR_IS_NOT_NULL = 'IS NOT NULL';

    protected $table;
    protected $column;
    protected $operator;
    protected $value;

    public function __construct(string $table, string $column, string $operator, $value)
    {
        $this->table = $table;
        $this->column = $column;
        $this->operator = $operator;
        $this->value = $value;
    }

    public function getOperator(): string
    {
        return $this->operator;
    }

    public function __toString(): string
    {
        return $this->getSQL();
    }

    public function getIdentifier(): string
    {
        return "`$this->table`.`$this->column`";
    }

    public function getSQL(): string
    {
        $components = [$this->getIdentifier(), $this->operator];

        if ($this->operator !== static::OPERATOR_IS_NULL && $this->operator !== static::OPERATOR_IS_NOT_NULL) {
            $components[] = '?';
        }

        return implode(' ', $components);
    }

    public function getValue()
    {
        return $this->value;
    }
}
