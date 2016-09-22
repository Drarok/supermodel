<?php

namespace Zerifas\Supermodel\Test\Transformer;

use Zerifas\Supermodel\Test\AbstractTestCase;
use Zerifas\Supermodel\Transformer\Boolean;

class BooleanTest extends AbstractTestCase
{
    /**
     * @dataProvider fromArrayDataProvider
     */
    public function testFromArray($input, $expected)
    {
        $this->assertSame($expected, Boolean::fromArray($input));
    }

    /**
     * @dataProvider toArrayDataProvider
     */
    public function testToArray($input, $expected)
    {
        $this->assertSame($expected, Boolean::toArray($input));
    }

    public function fromArrayDataProvider()
    {
        return [
            [null, null],
            [0, false],
            [1, true],
            [-1, true],
            [15, true],
            ['0', true],
            [[], true],
            [[1], true],
        ];
    }

    public function toArrayDataProvider()
    {
        return [
            [null, null],
            [false, 0],
            [true, 1],
            [-1, 1],
            [15, 1],
            ['0', 1],
            [[], 1],
            [[1], 1],
        ];
    }
}
