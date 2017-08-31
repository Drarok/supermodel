<?php

namespace Zerifas\Supermodel\Metadata;

use InvalidArgumentException;
use Zerifas\Supermodel\Cache\CacheInterface;
use Zerifas\Supermodel\Model;
use Zerifas\Supermodel\Relation\AbstractRelation;
use Zerifas\Supermodel\Transformers\TransformerInterface;

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
        /** @var Model $class */
        $key = "$class:tableName";
        if (!$this->cache->has($key)) {
            $this->cache->set($key, $class::getTableName());
        }

        return $this->cache->get($key);
    }

    /**
     * Get columns for given model class
     *
     * @param string $class String name of the class
     *
     * @return string[]
     */
    public function getColumns(string $class): array
    {
        /** @var Model $class */
        $key = "$class:columns";
        if (!$this->cache->has($key)) {
            $this->cache->set($key, $class::getColumns());
        }

        return $this->cache->get($key);
    }

    /**
     * Get value transformers for given model class
     *
     * @param string $class String name of the class
     *
     * @return TransformerInterface[]
     */
    public function getValueTransformers(string $class): array
    {
        /** @var Model $class */
        $key = "$class:valueTransformers";
        if (!$this->cache->has($key)) {
            $this->cache->set($key, $class::getValueTransformers());
        }

        return $this->cache->get($key);
    }

    /**
     * Get relations for given model class
     *
     * @param string $class String name of the class
     *
     * @return AbstractRelation[]
     */
    public function getRelations(string $class): array
    {
        /** @var Model $class */
        $key = "$class:relations";
        if (!$this->cache->has($key)) {
            $this->cache->set($key, $class::getRelations());
        }

        return $this->cache->get($key);
    }

    /**
     * Get a single relation for the given model class.
     *
     * @param string $class String name of the class
     * @param string $name Name of the relation
     *
     * @return AbstractRelation
     * @throws InvalidArgumentException
     */
    public function getRelation(string $class, string $name): AbstractRelation
    {
        $relations = $this->getRelations($class);
        $relation = $relations[$name] ?? null;

        if (!$relation) {
            throw new InvalidArgumentException("$name is not a relation of $class");
        }

        return $relation;
    }
}
