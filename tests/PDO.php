<?php

namespace Zerifas\Supermodel\Test;

class PDO extends \PDO
{
    protected $statements = [];

    public function prepare($statement, $options = NULL)
    {
        $this->statements[] = $statement;
        return new PDOStatement();
    }

    public function getStatements()
    {
        return $this->statements;
    }
}
