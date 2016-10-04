<?php

namespace Zerifas\Supermodel\Test\Console;

use PHPUnit_Framework_TestCase;
use Zerifas\Supermodel\Console\Config;

class ConfigTest extends PHPUnit_Framework_TestCase
{
    const TEST_CONFIG_PATHNAME = '/tmp/supermodel.json';

    protected static $config;

    public static function setUpBeforeClass()
    {
        static::$config = [
            'db' => [
                'hostname' => 'localhost',
                'username' => 'root',
                'password' => '',
                'dbname' => 'test',
                'charset' => 'utf8'
            ],
            'models' => [
                'namespace' => 'YourApp\\Model'
            ]
        ];

        file_put_contents(self::TEST_CONFIG_PATHNAME, json_encode(static::$config));
    }

    public static function tearDownAfterClass()
    {
        unlink(self::TEST_CONFIG_PATHNAME);
    }

    public function setUp()
    {
        parent::setUp();
        Config::setPath('/tmp');
    }

    public function testSomething()
    {
        $this->assertEquals(static::$config, Config::get());
    }

    public function testNoSuchFile()
    {
        $this->setExpectedException(
            'Exception',
            'No such file: /no-such-path/supermodel.json'
        );
        Config::setPath('/no-such-path');
        Config::get();
    }
}
