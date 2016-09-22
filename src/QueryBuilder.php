<?php

namespace Zerifas\Supermodel;

use PDO;

class QueryBuilder
{
    /**
     * Database connection.
     *
     * @var PDO
     */
    protected $db;

    /**
     * Columns that form the SELECT clause.
     *
     * @var array
     */
    protected $columns = [];

    /**
     * Main table to select from.
     *
     * @var string
     */
    protected $tableName;

    /**
     * Joined tables and their clauses.
     *
     * @var array
     */
    protected $joins = [];

    /**
     * Where clauses.
     *
     * @var array
     */
    protected $whereClauses = [];

    /**
     * Max number of rows to return.
     *
     * @var int|null
     */
    protected $limit;

    /**
     * Constructor.
     *
     * @param PDO $db Database connection.
     */
    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Set the columns to select.
     *
     * @param array $columns Array of columns, from YourModel::getColumns().
     *
     * @return self
     */
    public function select(array $columns)
    {
        $this->columns = $columns;
        return $this;
    }

    /**
     * Add columns to be selected.
     *
     * @param array $columns Array of columns, from YourModel::getColumns().
     *
     * @return self
     */
    public function addColumns(array $columns)
    {
        $this->columns = array_merge($this->columns, $columns);
        return $this;
    }

    /**
     * Set the main table to select from.
     *
     * @param string $tableName Name of the table.
     *
     * @return self
     */
    public function from($tableName)
    {
        $this->tableName = $tableName;
        return $this;
    }

    /**
     * Add a table to be joined.
     *
     * @param string $joinTable   Name of the table to join.
     * @param string $joinColumn  Column in the joined table.
     * @param string $otherColumn Column to match against.
     *
     * @return self
     */
    public function join($joinTable, $joinColumn, $otherColumn)
    {
        $this->joins[] = [
            'tableName' => $joinTable,
            'clause'    => sprintf('%s = %s', $joinColumn, $otherColumn),
        ];
        return $this;
    }

    /**
     * Set the WHERE clause(s).
     *
     * @param array $clauses Array of column => value.
     *
     * @return self
     */
    public function where(array $clauses)
    {
        $this->whereClauses = $clauses;
        return $this;
    }

    /**
     * Set the LIMIT clause.
     *
     * @param int $limit Max number of rows to return.
     *
     * @return self
     */
    public function limit($limit)
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * Build and execute the query.
     *
     * @return PDOStatement
     */
    public function execute()
    {
        $sql = 'SELECT ' . implode(', ', $this->columns);

        $sql .= ' FROM ' . $this->tableName;

        foreach ($this->joins as $join) {
            $sql .= sprintf(
                ' INNER JOIN %s ON %s',
                $join['tableName'],
                $join['clause']
            );
        }

        $values = null;
        if (count($this->whereClauses)) {
            $clauses = [];
            $values = [];

            foreach ($this->whereClauses as $column => $value) {
                $clauses[] = sprintf('%s = ?', $column);
                $values[] = $value;
            }

            $sql .= ' WHERE ' . implode(' AND ', $clauses);
        }

        if (is_int($this->limit)) {
            $sql .= sprintf(' LIMIT %d', $this->limit);
        }

        $stmt = $this->db->prepare($sql);

        if ($values) {
            $stmt->execute($values);
        } else {
            $stmt->execute();
        }

        return $stmt;
    }
}
