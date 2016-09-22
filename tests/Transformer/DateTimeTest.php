<?php

namespace Zerifas\Supermodel\Test\Transformer;

use Zerifas\Supermodel\Test\AbstractTestCase;
use Zerifas\Supermodel\Transformer\DateTime as DateTimeTransformer;

class DateTimeTest extends AbstractTestCase
{
    const FORMAT = 'Y-m-d H:i:s';

    /**
     * @dataProvider fromArrayDataProvider
     */
    public function testFromArray($input, $expected = null)
    {
        if ($expected === null) {
            $expected = $input;
        }

        $actual = DateTimeTransformer::fromArray($input);
        if ($actual instanceof \DateTime) {
            $actual = $actual->format(self::FORMAT);
        }

        $this->assertEquals($expected, $actual);
    }

    /**
     * @dataProvider toArrayDataProvider
     */
    public function testToArray(\DateTime $input = null, $expected = null)
    {
        if ($expected === null && $input !== null) {
            $expected = $input->format(self::FORMAT);
        }

        $actual = DateTimeTransformer::toArray($input);
        $this->assertEquals($expected, $actual);
    }

    public function testToArrayWithInvalidArgument()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid value passed to Zerifas\Supermodel\Transformer\DateTime::toArray'
        );
        DateTimeTransformer::toArray([]);
    }

    public function fromArrayDataProvider()
    {
        return [
            [null, null],
            ['2016-09-22 15:07:23'],
            ['2016-01-01 20:00:00'],
            ['1970-01-01 00:00:00'],
            ['2016-qq-01 00:00:00', false],
            ['', false],
        ];
    }

    public function toArrayDataProvider()
    {
        return [
            [null, null],
            [\DateTime::createFromFormat(self::FORMAT, '2016-09-22 15:07:23')],
            [\DateTime::createFromFormat(self::FORMAT, '2016-01-01 20:00:00')],
            [\DateTime::createFromFormat(self::FORMAT, '1970-01-01 00:00:00')],
        ];
    }
}
