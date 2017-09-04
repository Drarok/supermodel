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
     * @var array
     */
    private $values;

    public function __construct(array $aliases, string $clause, ...$values)
    {
        if (!preg_match('/^([\w`-]+)\.([\w`-]+)(.*?)$/', $clause, $matches)) {
            throw new InvalidArgumentException("$clause is not in the format alias.column");
        }

        // Get the name the user specified, map it back to the name used in the query.
        $name = trim($matches[1], '`');
        $this->alias = $aliases[$name];

        $this->column = trim($matches[2], '`');
        $this->suffix = trim($matches[3]);

        $this->values = $values;
    }

    public function toString(): string
    {
        $suffix = $this->suffix;

        if (strtoupper(substr($suffix, -4)) === 'IN ?') {
            $paramCount = count($this->values);
            $placeholders = '(' . implode(', ', array_fill(0, $paramCount, '?')) . ')';
            $suffix = str_replace('?', $placeholders, $suffix);
        }

        return trim("`$this->alias`.`$this->column` $suffix");
    }

    public function getAlias(): string
    {
        return $this->alias;
    }

    public function getColumn(): string
    {
        return $this->column;
    }

    public function getValues()
    {
        return $this->values;
    }
}
