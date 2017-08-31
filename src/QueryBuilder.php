<?php

namespace Zerifas\Supermodel;

use Generator;
use InvalidArgumentException;
use PDOStatement;

use Zerifas\Supermodel\Metadata\MetadataCache;
use Zerifas\Supermodel\Relation\AbstractRelation;
use Zerifas\Supermodel\Relation\BelongsToRelation;
use Zerifas\Supermodel\Relation\RelationInterface;

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

    public function where(string $clause, $value = null): QueryBuilder
    {
        $this->where[] = new QueryBuilderClause($clause, $value);
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

        $model = $this->model;
        if (($row = $stmt->fetch())) {
            return $model::createFromArray($row, $this->metadata, $this->alias);
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

        $model = $this->model;
        while (($row = $stmt->fetch())) {
            yield $model::createFromArray($row, $this->metadata, $this->alias);
        }
    }

    /**
     * Build and execute the query
     *
     * @return PDOStatement
     */
    private function execute(): PDOStatement
    {
        $table = $this->metadata->getTableName($this->model);

        $models = [
            $this->alias => $this->model,
        ];

        $sql = "SELECT * FROM `$table` AS `$this->alias`";

        $relations = $this->metadata->getRelations($this->model);

        foreach ($this->joins as $name => $joinAlias) {
            $relation = $relations[$name];

            $models[$joinAlias] = $joinModel = $relation->getModel();
            $foreignColumn = $relation->getForeignColumn();
            $localColumn = $relation->getLocalColumn();

            $joinTable = $this->metadata->getTableName($joinModel);

            $sql .= " INNER JOIN `$joinTable` AS `$joinAlias` ON " .
                "`$joinAlias`.`$foreignColumn` = `$this->alias`.`$localColumn`";
        }

        // TODO: Less looping and fetching of transformers.
        $params = [];
        if (count($this->where) > 0) {
            foreach ($this->where as $idx => $clause) {
                $model = $models[$clause->getAlias()];

                $transformers = $this->metadata->getValueTransformers($model);
                $value = $clause->getValue();

                if ($value !== null && ($transformer = $transformers[$clause->getColumn()] ?? null)) {
                    $value = $transformer::toArray($value);
                }

                if ($value !== null) {
                    $params[] = $value;
                }

            }

            $map = function (QueryBuilderClause $clause) {
                return $clause->toString();
            };

            $sql .= ' WHERE ' . implode(' AND ', array_map($map, $this->where));
        }

        if (count($this->orderBy) > 0) {
            $map = function (QueryBuilderClause $clause) {
                return $clause->toString() . ' ' . $clause->getValue();
            };

            $sql .= ' ORDER BY ' . implode(', ', array_map($map, $this->orderBy));
        }

        if ($this->limit !== null) {
            $sql .= ' LIMIT ' . $this->limit;

            if ($this->offset !== null) {
                $sql .= ' OFFSET ' . $this->offset;
            }
        }

        $stmt = $this->conn->prepare($sql);

        if (count($params) > 0) {
            $stmt->execute($params);
        } else {
            $stmt->execute();
        }

        return $stmt;
    }
}
