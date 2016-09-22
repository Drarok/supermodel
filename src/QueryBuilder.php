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
     * Class that this builder is operating on.
     *
     * @var string|null
     */
    protected $class;

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
     * @param PDO    $db    Database connection.
     * @param string $class Name of an AbstractModel subclass to use to resolve identifiers.
     */
    public function __construct(PDO $db, $class = null)
    {
        $this->db = $db;

        if ($class !== null) {
            if (!is_subclass_of($class, AbstractModel::class)) {
                throw new \InvalidArgumentException(sprintf(
                    '%s only accepts a class name of a subclass of %s as its second parameter',
                    static::class,
                    AbstractModel::class
                ));
            }

            $this->class = $class;
            $this->select($class::getColumns());
            $this->from($class::getTableName());
        }
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
        if ($this->class) {
            $class = $this->class;

            $prefixed = [];
            foreach ($clauses as $column => $value) {
                if (strpos($column, '.') === false) {
                    $column = $class::getColumn($column);
                }

                $prefixed[$column] = $value;
            }

            $clauses = $prefixed;
        }

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
        $sql = sprintf(
            'SELECT %s FROM `%s`',
            implode(', ', $this->columns),
            $this->tableName
        );

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
