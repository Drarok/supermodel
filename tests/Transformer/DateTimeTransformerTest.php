<?php

namespace Zerifas\Supermodel\Test\Transformer;

use PHPUnit\Framework\TestCase;
use Zerifas\Supermodel\Transformer\DateTimeTransformer;

class DateTimeTransformerTest extends TestCase
{
    const FORMAT = 'Y-m-d H:i:s';

    public function testFromArray()
    {
        $str = '2016-01-01 00:00:00';
        $actual = DateTimeTransformer::fromArray($str);

        $this->assertInstanceOf(\DateTime::class, $actual);
        $this->assertEquals($str, $actual->format(self::FORMAT));
    }

    public function testToArray()
    {
        $str = '2016-01-01 00:00:00';
        $date = \DateTime::createFromFormat(self::FORMAT, $str);
        $actual = DateTimeTransformer::toArray($date);

        $this->assertEquals($str, $actual);
    }
}
