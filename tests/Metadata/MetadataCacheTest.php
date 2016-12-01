<?php

namespace Zerifas\Supermodel\Test\Metadata;

use PHPUnit\Framework\TestCase;

use Zerifas\Supermodel\Cache\MemoryCache;
use Zerifas\Supermodel\Metadata\MetadataCache;
use Zerifas\Supermodel\SupermodelInterface;

class MetadataTestModel implements SupermodelInterface
{
    private static $tableName = 0;
    private static $columns = 0;
    private static $valueTransformers = 0;
    private static $relations = 0;

    public static function getTableName(): string
    {
        ++self::$tableName;
        return 'posts';
    }

    public static function getTableNameCount(): int
    {
        return self::$tableName;
    }

    public static function getColumns(): array
    {
        ++self::$columns;
        return [
            'id',
            'name',
        ];
    }

    public static function getColumnsCount(): int
    {
        return self::$columns;
    }

    public static function getValueTransformers(): array
    {
        ++self::$valueTransformers;
        return [];
    }

    public static function getValueTransformersCount(): int
    {
        return self::$valueTransformers;
    }

    public static function getRelations(): array
    {
        ++self::$relations;
        return [];
    }

    public static function getRelationsCount(): int
    {
        return self::$relations;
    }
}

class MetadataCacheTest extends TestCase
{
    /**
     * @var MetadataCache
     */
    protected $cache;

    public function setUp()
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
