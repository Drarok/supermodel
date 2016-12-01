<?php

namespace Zerifas\Supermodel\Metadata;

use Zerifas\Supermodel\Cache\CacheInterface;

class MetadataCache
{
    /**
     * @var CacheInterface
     */
    private $cache;

    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Get table name for given model class
     *
     * @param string $class String name of the class
     *
     * @return string
     */
    public function getTableName(string $class): string
    {
        $key = "$class:tableName";
        if (! $this->cache->has($key)) {
            $this->cache->set($key, $class::getTableName());
        }

        return $this->cache->get($key);
    }

    /**
     * Get columns for given model class
     *
     * @param string $class String name of the class
     *
     * @return String[]
     */
    public function getColumns(string $class): array
    {
        $key = "$class:columns";
        if (! $this->cache->has($key)) {
            $this->cache->set($key, $class::getColumns());
        }

        return $this->cache->get($key);
    }

    /**
     * Get value transformers for given model class
     *
     * @param string $class String name of the class
     *
     * @return array
     */
    public function getValueTransformers(string $class): array
    {
        $key = "$class:valueTransformers";
        if (! $this->cache->has($key)) {
            $this->cache->set($key, $class::getValueTransformers());
        }

        return $this->cache->get($key);
    }

    /**
     * Get relations for given model class
     *
     * @param string $class String name of the class
     *
     * @return ColumnReference[]
     */
    public function getRelations(string $class): array
    {
        $key = "$class:relations";
        if (! $this->cache->has($key)) {
            $this->cache->set($key, $class::getRelations());
        }

        return $this->cache->get($key);
    }
}
