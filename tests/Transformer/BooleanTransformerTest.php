<?php

namespace Zerifas\Supermodel\Test\Transformer;

use PHPUnit\Framework\TestCase;
use Zerifas\Supermodel\Transformer\BooleanTransformer;

class BooleanTransformerTest extends TestCase
{
    public function testFromArray()
    {
        $actual = BooleanTransformer::fromArray(1);
        $this->assertEquals(true, $actual);
    }

    public function testToArray()
    {
        $actual = BooleanTransformer::toArray(true);
        $this->assertEquals(1, $actual);
    }
}
