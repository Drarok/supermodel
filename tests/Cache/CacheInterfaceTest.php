<?php

namespace Zerifas\Supermodel\Test\Cache;

use PHPUnit\Framework\TestCase;

use Zerifas\Supermodel\Cache\CacheInterface;

abstract class CacheInterfaceTest extends TestCase
{
    /**
     * @var CacheInterface
     */
    protected $cache;

    public function setUp()
    {
        parent::setUp();

        $cache = $this->createInstance(true);
        $cache->set('KEY', 'VALUE');
        unset($cache);

        $this->cache = $this->createInstance();
    }

    public function testHas()
    {
        $this->assertTrue($this->cache->has('KEY'));
        $this->assertFalse($this->cache->has('NO_SUCH_KEY'));
    }

    public function testSetAndGet()
    {
        $this->cache->set('MY_KEY', true);
        $this->assertEquals(true, $this->cache->get('MY_KEY'));
        $this->assertEquals('VALUE', $this->cache->get('KEY'));
        $this->assertEquals(null, $this->cache->get('NO_SUCH_KEY'));
        $this->assertEquals('default', $this->cache->get('NO_SUCH_KEY', 'default'));
    }

    public function testDelete()
    {
        $this->cache->set('MY_KEY', true);
        $this->cache->delete('MY_KEY');

        $this->cache->delete('KEY');

        $this->assertEquals(false, $this->cache->has('KEY'));
        $this->assertEquals(false, $this->cache->has('MY_KEY'));
        $this->assertEquals(null, $this->cache->get('KEY'));
        $this->assertEquals('default', $this->cache->get('KEY', 'default'));
    }

    public function testClear()
    {
        $this->cache->set('MY_KEY', true);

        $this->cache->clear();

        $this->assertEquals(false, $this->cache->has('KEY'));
        $this->assertEquals(false, $this->cache->has('MY_KEY'));
        $this->assertEquals(null, $this->cache->get('KEY'));
        $this->assertEquals('default', $this->cache->get('KEY', 'default'));
    }

    abstract protected function createInstance(bool $empty = false): CacheInterface;
}
