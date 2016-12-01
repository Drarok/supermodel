<?php

namespace Zerifas\Supermodel\Test\Cache;

use Zerifas\Supermodel\Cache\CacheInterface;
use Zerifas\Supermodel\Cache\FileCache;

class FileCacheTest extends CacheInterfaceTest
{
    const CACHE_PATH = '/tmp/supermodel.test.cache';

    protected function createInstance(bool $empty = false): CacheInterface
    {
        if ($empty && file_exists(self::CACHE_PATH)) {
            unlink(self::CACHE_PATH);
        }

        return new FileCache(self::CACHE_PATH);
    }
}
