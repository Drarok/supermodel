<?php

namespace Zerifas\Supermodel\Test\Console\Database;

use PHPUnit_Framework_TestCase;

use Zerifas\Supermodel\Console\Database\Column;

class ColumnTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider dataProviderTypes
     */
    public function testTypes($type, $expectedType, $expectedUnsigned)
    {
        $column = new Column([
            'Field' => 'fakename',
            'Type' => $type,
            'Null' => 'NO',
        ]);

        $this->assertEquals('fakename', $column->getName());
        $this->assertEquals($expectedType, $column->getType());
        $this->assertEquals($expectedUnsigned, $column->isUnsigned());
    }


    public function dataProviderTypes()
    {
        return [
            ['int(11)', 'INT', false],
            ['int(11) unsigned', 'INT', true],
            ['datetime', 'DATETIME', false],
            ['date', 'DATE', false],
            ['time', 'TIME', false],
            ['text', 'TEXT', false],
            ['varchar(32)', 'VARCHAR', false],
            ['tinyint(3)', 'TINYINT', false],
            ['tinyint(3) unsigned', 'TINYINT', true],
        ];
    }

    /**
     * @dataProvider dataProviderNull
     */
    public function testNull($null, $expectedNull)
    {
        $column = new Column([
            'Field' => 'fakename',
            'Type' => 'varchar',
            'Null' => $null,
        ]);

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
