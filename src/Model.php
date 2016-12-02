<?php

namespace Zerifas\Supermodel;

use Zerifas\Supermodel\Metadata\MetadataCache;
use Zerifas\Supermodel\Relation\BelongsToRelation;

abstract class Model implements SupermodelInterface
{
    protected $id;

    public static function createFromArray(array $data, MetadataCache $metadata, string $alias = null): self
    {
        $obj = new static();

        if ($alias === null) {
            $alias = $metadata->getTableName(static::class);
        }

        $columns = $metadata->getColumns(static::class);
        $transformers = $metadata->getValueTransformers(static::class);

        foreach ($columns as $column) {
            $value = $data[$alias . '.' . $column] ?? null;

            if ($value !== null && ($transformer = $transformers[$column] ?? null)) {
                $value = $transformer::fromArray($value);
            }

            $obj->$column = $value;
        }

        /* @var BelongsToRelation $relation */
        foreach ($metadata->getRelations(static::class) as $name => $relation) {
            $joinModel = $relation->getJoinModel();
            $joinColumn = $relation->getJoinColumn();

            if (! empty($data["$name.$joinColumn"])) {
                $obj->$name = $joinModel::createFromArray($data, $metadata, $name);
            }
        }

        return $obj;
    }

    public static function column(string $column)
    {
        return static::createColumnReference('', $column, null);
    }

    public static function equal(string $column, $value): ColumnReference
    {
        return static::createColumnReference(ColumnReference::OPERATOR_EQUAL, $column, $value);
    }

    public static function notEqual(string $column, $value): ColumnReference
    {
        return static::createColumnReference(ColumnReference::OPERATOR_NOT_EQUAL, $column, $value);
    }

    public static function lessThan(string $column, $value): ColumnReference
    {
        return static::createColumnReference(ColumnReference::OPERATOR_LESS, $column, $value);
    }

    public static function greaterThan(string $column, $value): ColumnReference
    {
        return static::createColumnReference(ColumnReference::OPERATOR_GREATER, $column, $value);
    }

    public static function lessOrEqual(string $column, $value): ColumnReference
    {
        return static::createColumnReference(ColumnReference::OPERATOR_LESS_OR_EQUAL, $column, $value);
    }

    public static function greaterOrEqual(string $column, $value): ColumnReference
    {
        return static::createColumnReference(ColumnReference::OPERATOR_GREATER_OR_EQUAL, $column, $value);
    }

    public static function like(string $column, $value): ColumnReference
    {
        return static::createColumnReference(ColumnReference::OPERATOR_LIKE, $column, $value);
    }

    public static function isNull(string $column): ColumnReference
    {
        return static::createColumnReference(ColumnReference::OPERATOR_IS_NULL, $column, null);
    }

    private static function createColumnReference(string $operator, string $column, $value): ColumnReference
    {
        $class = static::class;
        $table = $class::getTableName();

        if (strpos($column, '.') !== false) {
            $keyPath = $column;
            $paths = explode('.', $column);
            $column = array_pop($paths);

            foreach ($paths as $name) {
                $relation = $class::getRelations()[$name] ?? null;

                if ($relation === null) {
                    $rootClass = static::class;
                    throw new \InvalidArgumentException(
                        "$name is not a defined relation of $class ($rootClass $keyPath)"
                    );
                }

                $class = $relation->getJoinModel();
                $table = $name;
            }
        }

        if ($value !== null) {
            /** @var TransformerInterface $transformer */
            $transformer = $class::getValueTransformers()[$column] ?? null;
            if ($transformer !== null) {
                $value = $transformer::toArray($value);
            }
        }

        return new ColumnReference($table, $column, $operator, $value);
    }

    public function __construct()
    {
    }

    public function setId(int $id): self
    {
        if ($this->id) {
            throw new \InvalidArgumentException('You cannot change the primary key of an existing object.');
        }

        $this->id = $id;
        return $this;
    }

    public function getId()
    {
        return $this->id;
    }

    public function toArray(MetadataCache $metadata): array
    {
        $data = [];
        $table = $metadata->getTableName(static::class);
        $transformers = $metadata->getValueTransformers(static::class);

        foreach ($metadata->getColumns(static::class) as $column) {
            $value = $this->$column ?? null;

            if ($value !== null && ($transformer = $transformers[$column] ?? null)) {
                $value = $transformer::toArray($value);
            }

            $data[$table . '.' . $column] = $value;
        }

        return $data;
    }
}
