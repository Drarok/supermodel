<?php

namespace Zerifas\Supermodel;

use Generator;
use PDOStatement;

use Zerifas\Supermodel\Metadata\MetadataCache;
use Zerifas\Supermodel\Relation\BelongsToRelation;

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
     * @var string
     */
    private $from;

    /**
     * @var BelongsToRelation[]
     */
    private $joins = [];

    /**
     * @var ColumnReference[]
     */
    private $where = [];

    /**
     * @var string[]
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

    public function __construct(Connection $conn, string $from, MetadataCache $metadata)
    {
        $this->conn = $conn;
        $this->from = $from;
        $this->metadata = $metadata;
    }

    /**
     * Join a pre-defined relation
     *
     * @param string $name Relation name to join
     *
     * @return QueryBuilder
     */
    public function join(string $name): QueryBuilder
    {
        $relation = $this->metadata->getRelations($this->from)[$name] ?? null;
        if ($relation === null) {
            throw new \InvalidArgumentException("$name is not a defined relation of $this->from");
        }

        $this->joins[$name] = $relation;
        return $this;
    }

    /**
     * Add where clauses
     *
     * @param ColumnReference[] $where
     *
     * @return QueryBuilder
     */
    public function where(ColumnReference $ref): QueryBuilder
    {
        $this->where[] = $ref;
        return $this;
    }

    /**
     * Add an order by clause
     *
     * @param ColumnReference $columnReference Reference to column to sort on
     * @param string $direction Optional direction, defaults to ASC
     *
     * @return QueryBuilder
     */
    public function orderBy(ColumnReference $columnReference, string $direction = 'ASC')
    {
        $direction = (strtoupper($direction) === 'DESC') ? 'DESC' : 'ASC';
        $this->orderBy[] = [$columnReference, $direction];
        return $this;
    }

    /**
     * Set the limit for this query
     *
     * @param int $limit
     *
     * @return QueryBuilder
     */
    public function limit(int $limit): QueryBuilder
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * Set the offset for this query
     *
     * @param int $offset
     *
     * @return QueryBuilder
     */
    public function offset(int $offset): QueryBuilder
    {
        $this->offset = $offset;
        return $this;
    }

    /**
     * Simple query by id column
     *
     * @param int $id
     *
     * @return QueryBuilder
     */
    public function byId(int $id): QueryBuilder
    {
        $model = $this->from;
        return $this->where($model::equal('id', $id));
    }

    /**
     * Execute the query, and get a single object.
     *
     * @return Model|bool
     */
    public function fetchOne()
    {
        $model = $this->from;
        $stmt = $this->limit(1)->execute();

        if (($row = $stmt->fetch())) {
            return $model::createFromArray($row, $this->metadata);
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
        $model = $this->from;
        $stmt = $this->execute();

        while (($row = $stmt->fetch())) {
            yield $model::createFromArray($row, $this->metadata);
        }
    }

    /**
     * Build and execute the query
     *
     * @return PDOStatement
     */
    private function execute(): PDOStatement
    {
        $fromTable = $this->metadata->getTableName($this->from);
        $sql = "SELECT * FROM `$fromTable`";

        foreach ($this->joins as $name => $relation) {
            $joinModel = $relation->getJoinModel();
            $joinColumn = $relation->getJoinColumn();
            $localColumn = $relation->getLocalColumn();

            $joinTable = $this->metadata->getTableName($joinModel);

            $sql .= " INNER JOIN `$joinTable` AS `$name` ON `$name`.`$joinColumn` = `$fromTable`.`$localColumn`";
        }

        $params = [];
        if (count($this->where) > 0) {
            $where = [];
            foreach ($this->where as $columnRef) {
                $where[] = $columnRef->getSQL();

                if ($columnRef->getOperator() !== ColumnReference::OPERATOR_IS_NULL) {
                    $params[] = $columnRef->getValue();
                }
            }
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        if (count($this->orderBy) > 0) {
            $map = function (array $order) {
                return $order[0]->getIdentifier() . ' ' . $order[1];
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
