<?php

namespace Zerifas\Supermodel\Test;

use PHPUnit\Framework\TestCase;

use Zerifas\Supermodel\ColumnReference;

class ColumnReferenceTest extends TestCase
{
    /**
     * @param string $operator
     *
     * @dataProvider dataProviderGetters
     */
    public function testGetters(string $operator)
    {
        $value = mt_rand(0, 1024);
        $ref = new ColumnReference('table', 'column', $operator, $value);

        if (in_array($operator, [ColumnReference::OPERATOR_IS_NULL, ColumnReference::OPERATOR_IS_NOT_NULL])) {
            $sql = "`table`.`column` $operator";
        } else {
            $sql = "`table`.`column` $operator ?";
        }

        $this->assertEquals('`table`.`column`', $ref->getIdentifier());
        $this->assertEquals($sql, $ref->getSQL());
        $this->assertEquals($sql, (string) $ref);
        $this->assertEquals($value, $ref->getValue());
    }

    public function dataProviderGetters()
    {
        return [
            [ColumnReference::OPERATOR_EQUAL],
            [ColumnReference::OPERATOR_NOT_EQUAL],
            [ColumnReference::OPERATOR_LESS],
            [ColumnReference::OPERATOR_GREATER],
            [ColumnReference::OPERATOR_LESS_OR_EQUAL],
            [ColumnReference::OPERATOR_GREATER_OR_EQUAL],
            [ColumnReference::OPERATOR_LIKE],
            [ColumnReference::OPERATOR_IS_NULL],
            [ColumnReference::OPERATOR_IS_NOT_NULL],
        ];
    }
}
