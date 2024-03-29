<?php

namespace Zerifas\Supermodel;

use Zerifas\Supermodel\Metadata\MetadataCache;
use Zerifas\Supermodel\Relation\AbstractRelation;
use Zerifas\Supermodel\Relation\BelongsToRelation;
use Zerifas\Supermodel\Relation\HasManyRelation;
use Zerifas\Supermodel\Relation\ManyToManyRelation;
use Zerifas\Supermodel\Transformer\TransformerInterface;

abstract class Model implements SupermodelInterface
{
    protected $id;

    protected $__data = [];

    public static function createFromArray(array $data, MetadataCache $metadata, string $alias = null): self
    {
        $obj = new static();

        $columns = $metadata->getColumns(static::class);
        $transformers = $metadata->getValueTransformers(static::class);

        if ($alias === null) {
            $alias = $metadata->getTableName(static::class);
        }

        /** @var string $column */
        foreach ($columns as $column) {
            $value = $data["$alias.$column"] ?? null;

            /** @var TransformerInterface $transformer */
            if ($value !== null && ($transformer = $transformers[$column] ?? null)) {
                $value = $transformer::fromArray($value);
            }

            $obj->$column = $value;
        }

        foreach ($metadata->getRelations(static::class) as $name => $relation) {
            if (!($relation instanceof AbstractRelation)) {
                $class = static::class;
                throw new \UnexpectedValueException("Relation $name is invalid in $class");
            }

            /** @var Model $joinModel */
            $joinModel = $relation->getModel();
            $foreignColumn = $relation->getForeignColumn();

            if (!isset($data["$name.$foreignColumn"]) && !isset($data['.' . $name])) {
                continue;
            }

            if ($relation instanceof HasManyRelation || $relation instanceof ManyToManyRelation) {
                $obj->$name = $data['.' . $name];
            } elseif ($relation instanceof BelongsToRelation) {
                if (!empty($data["$name.$foreignColumn"])) {
                    $obj->$name = $joinModel::createFromArray($data, $metadata, $name);
                }
            }
        }

        return $obj;
    }

    public function __construct()
    {
    }

    public function __set(string $name, mixed $value): void
    {
        $this->__data[$name] = $value;
    }

    public function __get(string $name): mixed
    {
        return $this->__data[$name];
    }

    public function __isset(string $name): bool
    {
        return array_key_exists($name, $this->__data);
    }

    public function __unset(string $name): void
    {
        unset($this->__data[$name]);
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
