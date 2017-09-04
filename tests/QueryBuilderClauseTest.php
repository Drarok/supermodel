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
        new QueryBuilderClause([], 'invalid-example');
    }

    public function testValidClause()
    {
        $aliases = ['p' => 'posts'];
        $clause = new QueryBuilderClause($aliases, 'p.id = ?', 15);

        $this->assertEquals('posts', $clause->getAlias());
        $this->assertEquals('id', $clause->getColumn());
        $this->assertEquals([15], $clause->getValues());
    }

    public function testToString()
    {
        $aliases = ['p' => 'posts'];
        $clause = new QueryBuilderClause($aliases, 'p.id IN ?', 1, 2, 3);
        $this->assertEquals('`posts`.`id` IN (?, ?, ?)', $clause->toString());
    }
}
