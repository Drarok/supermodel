<?php

namespace Zerifas\Supermodel\Test\Metadata;

use PHPUnit\Framework\TestCase;
use Zerifas\Supermodel\Cache\MemoryCache;
use Zerifas\Supermodel\Metadata\MetadataCache;
use Zerifas\Supermodel\Test\Model\MetadataTestModel;

class MetadataCacheTest extends TestCase
{
    /**
     * @var MetadataCache
     */
    protected $cache;

    public function setUp(): void
    {
        parent::setUp();
        $this->cache = new MetadataCache(new MemoryCache());
    }

    public function testGetTableName()
    {
        $expected = $this->cache->getTableName(MetadataTestModel::class);
        $actual = $this->cache->getTableName(MetadataTestModel::class);
        $this->assertEquals($expected, $actual);
        $this->assertEquals(1, MetadataTestModel::getTableNameCount());
    }

    public function testGetColumns()
    {
        $expected = $this->cache->getColumns(MetadataTestModel::class);
        $actual = $this->cache->getColumns(MetadataTestModel::class);
        $this->assertEquals($expected, $actual);
        $this->assertEquals(1, MetadataTestModel::getColumnsCount());
    }

    public function testGetValueTransformers()
    {
        $expected = $this->cache->getValueTransformers(MetadataTestModel::class);
        $actual = $this->cache->getValueTransformers(MetadataTestModel::class);
        $this->assertEquals($expected, $actual);
        $this->assertEquals(1, MetadataTestModel::getValueTransformersCount());
    }

    public function testGetRelations()
    {
        $expected = $this->cache->getRelations(MetadataTestModel::class);
        $actual = $this->cache->getRelations(MetadataTestModel::class);
        $this->assertEquals($expected, $actual);
        $this->assertEquals(1, MetadataTestModel::getRelationsCount());
    }
}
