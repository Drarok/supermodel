<?php

namespace Zerifas\Supermodel\Test\Console;

use PHPUnit_Framework_TestCase;
use Zerifas\Supermodel\Console\Application;

class ApplicationTest extends PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        $app = new Application();

        $this->assertEquals(['help', 'list', 'generate'], array_keys($app->all()));
    }
}
