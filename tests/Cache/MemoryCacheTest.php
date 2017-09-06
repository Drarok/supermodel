<?php

namespace Zerifas\Supermodel\Test\Cache;

use Zerifas\Supermodel\Cache\CacheInterface;
use Zerifas\Supermodel\Cache\MemoryCache;

class MemoryCacheTest extends CacheInterfaceTest
{
    protected function createInstance(bool $empty = false): CacheInterface
    {
        static $cache = null;

        if ($empty) {
            $cache = new MemoryCache();
        }

        return $cache;
    }
}
