<?php

namespace Zerifas\Supermodel;

use InvalidArgumentException;

class QueryBuilderClause
{
    /**
     * @var string
     */
    private $alias;

    /**
     * @var string
     */
    private $column;

    /**
     * @var string
     */
    private $suffix;

    /**
     * @var mixed
     */
    private $value;

    public function __construct(string $clause, $value = null)
    {
        if (!preg_match('/^([\w`-]+)\.([\w`-]+)(.*?)$/', $clause, $matches)) {
            throw new InvalidArgumentException("$clause is not in the format alias.column");
        }

        $this->alias = trim($matches[1], '`');
        $this->column = trim($matches[2], '`');
        $this->suffix = trim($matches[3]);

        $this->value = $value;
    }

    public function toString(): string
    {
        return trim("`$this->alias`.`$this->column` $this->suffix");
    }

    public function getAlias(): string
    {
        return $this->alias;
    }

    public function getColumn(): string
    {
        return $this->column;
    }

    public function getValue()
    {
        return $this->value;
    }
}
