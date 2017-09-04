<?php

namespace Zerifas\Supermodel\Test;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Zerifas\Supermodel\QueryBuilderClause;

class QueryBuilderClauseTest extends TestCase
{
    public function testInvalidClause()
    {
        $this->expectException(InvalidArgumentException::class);

        new QueryBuilderClause('invalid-example');
    }

    public function testValidClause()
    {
        $clause = new QueryBuilderClause('p.id = ?', 15);

        $this->assertEquals('p', $clause->getAlias());
        $this->assertEquals('id', $clause->getColumn());
        $this->assertEquals([15], $clause->getValues());
    }

    public function testToString()
    {
        $clause = new QueryBuilderClause('p.id IN ?', 1, 2, 3);
        $this->assertEquals('`p`.`id` IN (?, ?, ?)', $clause->toString());
    }
}
