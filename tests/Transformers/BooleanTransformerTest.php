<?php

namespace Zerifas\Supermodel\Test\Transformers;

use PHPUnit\Framework\TestCase;

use Zerifas\Supermodel\Transformers\BooleanTransformer;

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
