<?php

namespace Zerifas\Supermodel\Console\Database;

class Column
{
    protected $name;
    protected $type;
    protected $unsigned;
    protected $null;

    public function __construct(array $row)
    {
        if (! preg_match('/^(\w+)(?:\(\d+\))? ?(unsigned)?$/i', $row['Type'], $matches)) {
            throw new \InvalidArgumentException('Failed to parse column type: ' . $row['Type']);
        }

        $this->name = $row['Field'];
        $this->type = strtoupper($matches[1]);
        $this->null = $row['Null'] === 'YES';
        $this->unsigned = array_key_exists(2, $matches) && strtolower($matches[2]) == 'unsigned';
    }

    public function getName()
    {
        return $this->name;
    }

    public function getType()
    {
        return $this->type;
    }

    public function isUnsigned()
    {
        return $this->unsigned;
    }

    public function isNull()
    {
        return $this->null;
    }
}
