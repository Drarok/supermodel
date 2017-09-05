<?php

namespace Zerifas\Supermodel;

use Generator;
use PDOStatement;

use Zerifas\Supermodel\Metadata\MetadataCache;
use Zerifas\Supermodel\Relation\BelongsToRelation;
use Zerifas\Supermodel\Relation\HasManyRelation;
use Zerifas\Supermodel\Relation\ManyToManyRelation;
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
    private $aliases;

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
     * @var int|null
     */
    private $limit = null;

    /**
     * @var int|null
     */
    private $offset = null;

    /**
     * @var callable|null
     */
    private $before;

    public function __construct(Connection $conn, string $model, string $alias)
    {
        $this->metadata = $conn->getMetadata();

        $this->conn = $conn;
        $this->model = $model;

        $this->aliases[$alias] = $this->metadata->getTableName($model);
    }

    public function join(string $relation, string $alias): QueryBuilder
    {
        // This will throw if the relation doesn't exist, but we don't need its result.
        $this->metadata->getRelation($this->model, $relation);

        if (in_array($relation, $this->joins)) {
            throw new \InvalidArgumentException("Duplicate join relation: $relation");
        }

        if (isset($this->aliases[$alias])) {
            throw new \InvalidArgumentException("Duplicate join alias: $alias");
        }

        $this->joins[] = $relation;
        $this->aliases[$alias] = $relation;

        return $this;
    }

    public function where(string $clause, ...$values): QueryBuilder
    {
        $this->where[] = new QueryBuilderClause($this->aliases, $clause, ...$values);
        return $this;
    }

    public function orderBy(string $order, string $direction = 'ASC')
    {
        $direction = (strtoupper($direction) === 'DESC') ? 'DESC' : 'ASC';
        $this->orderBy[] = new QueryBuilderClause($this->aliases, $order, $direction);
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

    public function before(callable $before = null)
    {
        $this->before = $before;
        return $this;
    }

    public function count()
    {
        $sql = $this->generateSQL(true);
        $params = $this->getParams();

        if (($before = $this->before)) {
            $before($sql, $params);
        }

        $stmt = $this->conn->prepare($sql);
        if (is_array($params) && count($params)) {
            $stmt->execute($params);
        } else {
            $stmt->execute();
        }

        return (int) $stmt->fetchColumn();
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
            $relatedCache = $this->fillRelatedCache([$row]);
            return $this->createInstance($row, $relatedCache);
        }

        return false;
    }

    /**
     * Execute the query, and get a Generator returning model objects.
     *
     * @return Generator|false
     */
    public function fetchAll(): Generator
    {
        $stmt = $this->execute();
        $rows = $stmt->fetchAll();

        if ($rows === false) {
            throw new \UnexpectedValueException('Failed to execute query!');
        }

        $relatedCache = $this->fillRelatedCache($rows);
        foreach ($rows as $row) {
            yield $this->createInstance($row, $relatedCache);
        }
    }

    /**
     * Pre-fetch any rows for HasManyRelation and ManyToManyRelation.
     *
     * @param array $rows
     *
     * @return array
     */
    private function fillRelatedCache(array $rows): array
    {
        $relatedCache = [];

        $relations = $this->metadata->getRelations($this->model);

        foreach ($this->joins as $name) {
            $relation = $relations[$name];

            // BelongsToRelation is handled already at this point.
            if ($relation instanceof BelongsToRelation) {
                continue;
            }

            $relatedCache[$name] = [];

            $ids = [];
            foreach ($rows as $row) {
                $foreignIds = array_map('intval', explode(',', $row['.' . $name] ?? ''));
                foreach ($foreignIds as $id) {
                    $ids[$id] = true;
                }
            }

            $objects = (new QueryBuilder($this->conn, $relation->getModel(), $name))
                ->before($this->before)
                ->where($name . '.id IN ?', ...array_keys($ids))
                ->fetchAll();

            foreach ($objects as $obj) {
                $relatedCache[$name][$obj->getId()] = $obj;
            }
        }

        return $relatedCache;
    }

    /**
     * Build and execute the query
     *
     * @return PDOStatement
     */
    private function execute(): PDOStatement
    {
        $sql = $this->generateSQL();
        $params = $this->getParams();

        if (($before = $this->before)) {
            $before($sql, $params ?: null);
        }

        $stmt = $this->conn->prepare($sql);

        if (is_array($params) && count($params) > 0) {
            $stmt->execute($params);
        } else {
            $stmt->execute();
        }

        return $stmt;
    }

    private function getParams()
    {
        $params = [];

        $table = $this->metadata->getTableName($this->model);

        /** @var TransformerInterface[] $transformers */
        $transformers = [
            $table => $this->metadata->getValueTransformers($this->model),
        ];

        $relations = $this->metadata->getRelations($this->model);
        foreach ($relations as $name => $relation) {
            $transformers[$name] = $this->metadata->getValueTransformers($relation->getModel());
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

    private function generateSQL(bool $countOnly = false): string
    {
        $table = $this->metadata->getTableName($this->model);

        $select = ["`$table`.*"];
        $sql = '';

        $relations = $this->metadata->getRelations($this->model);

        $hasMany = false;
        foreach ($this->joins as $name) {
            $relation = $relations[$name];

            $joinModel = $relation->getModel();
            $foreignColumn = $relation->getForeignColumn();
            $localColumn = $relation->getLocalColumn();

            $joinTable = $this->metadata->getTableName($joinModel);

            if ($relation instanceof HasManyRelation) {
                $hasMany = true;
                $sql .= " LEFT OUTER JOIN `$joinTable` AS `$name` ON " .
                    "`$name`.`$foreignColumn` = `$table`.`$localColumn`";
                $select[] = "GROUP_CONCAT(`$name`.`id`) AS `$name`";
            } elseif ($relation instanceof ManyToManyRelation) {
                $hasMany = true;
                $throughTable = $relation->getThroughTable();
                $nearJoinColumn = $relation->getNearJoinColumn();
                $farJoinColumn = $relation->getFarJoinColumn();

                $sql .= " LEFT OUTER JOIN `$throughTable` ON "
                    . "`$throughTable`.`$nearJoinColumn` = `$table`.`id`";
                $sql .= " LEFT OUTER JOIN `$joinTable` AS `$name` ON "
                    . "`$name`.`id` = `$throughTable`.`$farJoinColumn`";
                $select[] = "GROUP_CONCAT(`$name`.`id`) AS `$name`";
            } elseif ($relation instanceof BelongsToRelation) {
                $sql .= " INNER JOIN `$joinTable` AS `$name` ON " .
                    "`$name`.`$foreignColumn` = `$table`.`$localColumn`";
                $select[] = "`$name`.*";
            }
        }

        if (count($this->where) > 0) {
            $map = function (QueryBuilderClause $clause) {
                return $clause->toString();
            };

            $sql .= ' WHERE ' . implode(' AND ', array_map($map, $this->where));
        }

        // Override the select if we're only counting.
        if ($countOnly) {
            $select = [
                "COUNT(DISTINCT `$table`.`id`) AS `count`"
            ];
        }

        $sql = sprintf(
            "SELECT %s FROM `$table`%s",
            implode(', ', $select),
            $sql
        );

        if (!$countOnly) {
            if ($hasMany) {
                $sql .= " GROUP BY `$table`.`id`";
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
        }

        return $sql;
    }

    /**
     * Pre-transform HasManyRelation data into objects.
     *
     * @param array $data Raw data to create objects from
     * @param array $relatedCache Cache of related objects
     *
     * @return Model
     */
    private function createInstance(array $data, array $relatedCache): Model
    {
        $relations = $this->metadata->getRelations($this->model);

        foreach ($this->joins as $name) {
            $cache = $relatedCache[$name] ?? [];

            $relation = $relations[$name];

            // BelongsToRelation is handled directly in Model::createFromArray.
            if ($relation instanceof BelongsToRelation) {
                continue;
            }

            if (($data['.' . $name] ?? null) === null) {
                continue;
            }

            // Get an array of ids we need for this instance.
            $ids = array_map('intval', explode(',', $data['.' . $name] ?? ''));

            // Don't hit the database for objects we have in the cache.
            $objects = $missing = [];
            foreach ($ids as $id) {
                if (!isset($cache[$id])) {
                    $missing[] = $id;
                    continue;
                }
                $objects[] = $cache[$id];
            }

            if (count($missing) > 0) {
                $missing = implode(', ', $missing);
                $error = "Cannot use partially-filled cache for $this->model.$name - missing ids: $missing";
                throw new \InvalidArgumentException($error);
            }

            $data['.' . $name] = $objects;
        }

        /** @var Model $class */
        $model = $this->model;
        return $model::createFromArray($data, $this->metadata);
    }
}
