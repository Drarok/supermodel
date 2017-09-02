<?php

namespace Zerifas\Supermodel;

use Generator;
use InvalidArgumentException;
use PDOStatement;

use Zerifas\Supermodel\Metadata\MetadataCache;
use Zerifas\Supermodel\Relation\AbstractRelation;
use Zerifas\Supermodel\Relation\BelongsToRelation;
use Zerifas\Supermodel\Relation\HasManyRelation;
use Zerifas\Supermodel\Relation\RelationInterface;
use Zerifas\Supermodel\Transformers\TransformerInterface;

class QueryBuilder
{
    /**
     * @var Connection
     */
    private $conn;

    /**
     * @var MetadataCache
     */
    private $metadata;

    /**
     * @var string|Model
     */
    private $model;

    /**
     * @var string
     */
    private $alias;

    /**
     * @var string[]
     */
    private $joins = [];

    /**
     * @var QueryBuilderClause[]
     */
    private $where = [];

    /**
     * @var QueryBuilderClause[]
     */
    private $orderBy = [];

    /**
     * @var ?int
     */
    private $limit = null;

    /**
     * @var ?int
     */
    private $offset = null;

    public function __construct(Connection $conn, string $model, string $alias)
    {
        $this->conn = $conn;
        $this->model = $model;
        $this->alias = $alias;

        $this->metadata = $conn->getMetadata();
    }

    public function join(string $name, string $alias): QueryBuilder
    {
        // This will throw if the relation doesn't exist, but we don't need its result.
        $this->metadata->getRelation($this->model, $name);

        $this->joins[$name] = $alias;
        return $this;
    }

    public function where(string $clause, ...$values): QueryBuilder
    {
        $this->where[] = new QueryBuilderClause($clause, ...$values);
        return $this;
    }

    public function orderBy(string $order, string $direction = 'ASC')
    {
        $direction = (strtoupper($direction) === 'DESC') ? 'DESC' : 'ASC';
        $this->orderBy[] = new QueryBuilderClause($order, $direction);
        return $this;
    }

    public function limit(int $limit): QueryBuilder
    {
        $this->limit = $limit ?: null;
        return $this;
    }

    public function offset(int $offset): QueryBuilder
    {
        $this->offset = $offset ?: null;
        return $this;
    }

    public function byId(int $id): QueryBuilder
    {
        return $this->where("{$this->alias}.id = ?", $id);
    }

    /**
     * Execute the query, and get a single object.
     *
     * @return Model|false
     */
    public function fetchOne()
    {
        $stmt = $this->limit(1)->execute();

        if (($row = $stmt->fetch())) {
            return $this->createInstance($row);
        }

        return false;
    }

    /**
     * Execute the query, and get a Generator returning model objects.
     *
     * @return Generator
     */
    public function fetchAll(): Generator
    {
        $stmt = $this->execute();

        // TODO: Pre-fetch all the related items here!

        while (($row = $stmt->fetch())) {
            yield $this->createInstance($row);
        }
    }

    /**
     * Build and execute the query
     *
     * @return PDOStatement
     */
    private function execute(): PDOStatement
    {
        $sql = $this->generateSQL();
        $stmt = $this->conn->prepare($sql);

        $params = $this->getParams();

        if (count($params) > 0) {
            $stmt->execute($params);
        } else {
            $stmt->execute();
        }

        return $stmt;
    }

    private function getParams()
    {
        $params = [];

        $transformers = [
            $this->alias => $this->metadata->getValueTransformers($this->model),
        ];

        $relations = $this->metadata->getRelations($this->model);

        foreach ($this->joins as $name => $alias) {
            $relation = $relations[$name];
            $model = $relation->getModel();
            $transformers[$alias] = $this->metadata->getValueTransformers($model);
        }

        foreach ($this->where as $clause) {
            $alias = $clause->getAlias();
            $modelTransformers = $transformers[$alias];

            $values = $clause->getValues();

            foreach ($values as $value) {
                /** @var TransformerInterface $t */
                if ($value !== null && ($t = $modelTransformers[$clause->getColumn()] ?? null)) {
                    $value = $t::toArray($value);
                }

                if ($value !== null) {
                    $params[] = $value;
                }
            }
        }

        return $params;
    }


    private function generateSQL(): string
    {
        $table = $this->metadata->getTableName($this->model);

        $select = [
            "`$this->alias`.*",
        ];
        $sql = '';

        $relations = $this->metadata->getRelations($this->model);

        foreach ($this->joins as $name => $joinAlias) {
            $relation = $relations[$name];

            $joinModel = $relation->getModel();
            $foreignColumn = $relation->getForeignColumn();
            $localColumn = $relation->getLocalColumn();

            $joinTable = $this->metadata->getTableName($joinModel);

            if ($relation instanceof HasManyRelation) {
                $sql .= " LEFT OUTER JOIN `$joinTable` AS `$joinAlias` ON " .
                    "`$joinAlias`.`$foreignColumn` = `$this->alias`.`$localColumn`";
                $select[] = "GROUP_CONCAT(`$joinAlias`.`id`) AS `$name`";
            } elseif ($relation instanceof BelongsToRelation) {
                $sql .= " INNER JOIN `$joinTable` AS `$joinAlias` ON " .
                    "`$joinAlias`.`$foreignColumn` = `$this->alias`.`$localColumn`";
            } else {
                $class = get_class($relation);
                throw new \InvalidArgumentException("Cannot handle $class as a relation of $this->model");
            }
        }

        if (count($this->where) > 0) {
            $map = function (QueryBuilderClause $clause) {
                return $clause->toString();
            };

            $sql .= ' WHERE ' . implode(' AND ', array_map($map, $this->where));
        }

        $sql = sprintf(
            "SELECT %s FROM `$table` AS `$this->alias`%s",
            implode(', ', $select),
            $sql
        );

        if (count($select) > 1) {
            $sql .= " GROUP BY `$this->alias`.`id`";
        }

        if (count($this->orderBy) > 0) {
            $map = function (QueryBuilderClause $clause) {
                return $clause->toString() . ' ' . $clause->getValues()[0];
            };

            $sql .= ' ORDER BY ' . implode(', ', array_map($map, $this->orderBy));
        }

        if ($this->limit !== null) {
            $sql .= ' LIMIT ' . $this->limit;

            if ($this->offset !== null) {
                $sql .= ' OFFSET ' . $this->offset;
            }
        }

        return $sql;
    }

    /**
     * Pre-transform HasManyRelation data into objects.
     *
     * @param array $data Raw data to create objects from
     *
     * @return Model
     */
    private function createInstance(array $data): Model
    {
        $relations = $this->metadata->getRelations($this->model);

        foreach ($this->joins as $name => $alias) {
            $relation = $relations[$name];

            if (!($relation instanceof HasManyRelation)) {
                continue;
            }

            $foreignIds = array_map('intval', explode(',', $data[$name]));

            $objects = (new QueryBuilder($this->conn, $relation->getModel(), $name))
                ->where($name . '.id IN ?', ...$foreignIds)
                ->fetchAll()
            ;

            $data[$name] = iterator_to_array($objects);
        }

        /** @var Model $class */
        $model = $this->model;
        return $model::createFromArray($data, $this->metadata, $this->alias);
    }
}
