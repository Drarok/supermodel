<?php

namespace Zerifas\Supermodel\Test\Console\Database;

use PHPUnit_Framework_TestCase;

use Zerifas\Supermodel\Console\Database\Column;

class ColumnTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider dataProviderTypes
     */
    public function testTypes($type, $expectedType, $expectedLimit, $expectedUnsigned)
    {
        $column = new Column([
            'Field' => 'fakename',
            'Type' => $type,
            'Null' => 'NO',
        ]);

        $this->assertEquals('fakename', $column->getName());
        $this->assertEquals($expectedType, $column->getType());
        $this->assertSame($expectedLimit, $column->getLimit());
        $this->assertSame($expectedUnsigned, $column->isUnsigned());
    }


    public function dataProviderTypes()
    {
        return [
            ['int(11)', 'INT', 11, false],
            ['int(11) unsigned', 'INT', 11, true],
            ['datetime', 'DATETIME', null, false],
            ['date', 'DATE', null, false],
            ['time', 'TIME', null, false],
            ['text', 'TEXT', null, false],
            ['varchar(32)', 'VARCHAR', 32, false],
            ['tinyint(3)', 'TINYINT', 3, false],
            ['tinyint(3) unsigned', 'TINYINT', 3, true],
            ['bit(1)', 'BIT', 1, false],
            ['float(8,2)', 'FLOAT', '8,2', false],
            ['float(8,2) unsigned', 'FLOAT', '8,2', true],
            ['decimal(8,2)', 'DECIMAL', '8,2', false],
            ['decimal(8,2) unsigned', 'DECIMAL', '8,2', true],
        ];
    }

    /**
     * @dataProvider dataProviderNull
     */
    public function testNull($null, $expectedNull)
    {
        $column = new Column([
            'Field' => 'fakename',
            'Type' => 'varchar(32)',
            'Null' => $null,
        ]);

        $this->assertEquals('VARCHAR', $column->getType());
        $this->assertSame(32, $column->getLimit());
        $this->assertEquals($expectedNull, $column->isNull());
    }

    public function dataProviderNull()
    {
        return [
            ['NO', false],
            ['YES', true],
        ];
    }

    public function testInvalidColumnType()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Failed to parse column type: INVALID COL TYPE'
        );

        new Column([
            'Field' => 'fakename',
            'Type' => 'INVALID COL TYPE',
            'Null' => 'NO',
        ]);
    }
}
