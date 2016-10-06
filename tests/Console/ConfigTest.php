<?php

namespace Zerifas\Supermodel\Test\Console;

use PHPUnit_Framework_TestCase;
use Zerifas\Supermodel\Console\Config;

class ConfigTest extends PHPUnit_Framework_TestCase
{
    const TEST_CONFIG_PATHNAME = '/tmp/supermodel.json';

    private static function createValidConfig()
    {
        $config = [
            'db'     => [
                'host'     => 'localhost',
                'username' => 'root',
                'password' => '',
                'dbname'   => 'test',
                'charset'  => 'utf8',
            ],
            'models' => [
                'namespace' => 'YourApp\\Model'
            ]
        ];

        file_put_contents(self::TEST_CONFIG_PATHNAME, json_encode($config));

        return (object) [
            'db'     => (object) $config['db'],
            'models' => (object) $config['models'],
        ];
    }

    private static function createInvalidConfig()
    {
        file_put_contents(self::TEST_CONFIG_PATHNAME, json_encode([]));
    }

    public function setUp()
    {
        parent::setUp();
        Config::setPath('/tmp');
    }

    public function tearDown()
    {
        if (file_exists(self::TEST_CONFIG_PATHNAME)) {
            unlink(self::TEST_CONFIG_PATHNAME);
        }
        parent::tearDown();
    }

    public function testValidConfig()
    {
        $config = static::createValidConfig();
        $this->assertEquals($config, Config::get());
    }

    public function testInvalidConfig()
    {
        $this->setExpectedException(
            'Exception',
            'Invalid config: Key path \'db\' is required, but missing. Key path \'models\' is required, but missing.'
        );
        static::createInvalidConfig();
        Config::get();
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
