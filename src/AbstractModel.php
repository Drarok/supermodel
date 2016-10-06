<?php

namespace Zerifas\Supermodel;

use PDO;

abstract class AbstractModel
{
    /**
     * Version of Supermodel.
     */
    const VERSION = '1.3.0';

    /**
     * Array of column names as contained in the _table_. If you want to change
     * the name used in the model, refer to the `columnMap` static property below.
     *
     * @var array
     */
    protected static $columns = [];

    /**
     * Column -> property map keyed on column name.
     *
     * @var array
     */
    protected static $columnMap = [];

    /**
     * Value transformers, keyed on column name.
     *
     * @var array
     */
    protected static $valueTransformers = [];

    /**
     * Deleted flag.
     *
     * @var bool
     */
    protected $deleted = false;

    /**
     * Primary key value.
     *
     * @var int
     */
    protected $id;

    /**
     * Define the recommended options to pass to PDO.
     *
     * @return array
     */
    public static function getPDOOptions()
    {
        return [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_STRINGIFY_FETCHES  => false,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
    }

    /**
     * Get the columns as defined in static::$columns ready for use in a SQL statement.
     *
     * @return array
     */
    public static function getColumns()
    {
        $tableName = static::getTableName();
        return array_map(function ($c) use ($tableName) {
            return sprintf('`%1$s`.`%2$s` AS `%1$s:%2$s`', $tableName, $c);
        }, static::$columns);
    }

    public static function getColumn($columnName)
    {
        return sprintf('`%s`.`%s`', static::getTableName(), $columnName);
    }

    /**
     * Create an instance from an array of data, keyed by storage column name.
     *
     * @param array  $array Array of data, keyed on column name.
     * @param PDO    $db    Database connection.
     *
     * @return AbstractModel
     */
    public static function createFromArray(array $array, PDO $db = null)
    {
        $tableName = static::getTableName();

        $instance = new static($db);

        $columns = static::$columns;
        $map = static::$columnMap;

        foreach ($columns as $column) {
            // Use the mapped name if set, else just use the column name.
            if (array_key_exists($column, $map)) {
                $setter = 'set' . ucfirst($map[$column]);
            } else {
                $setter = 'set' . ucfirst($column);
            }

            // Get the value using the table name as a prefix.
            $dataKey = $tableName . ':' . $column;
            $value = array_key_exists($dataKey, $array) ? $array[$dataKey] : null;

            // Apply transform if one is set.
            if (array_key_exists($column, static::$valueTransformers)) {
                $transformer = [static::$valueTransformers[$column], 'fromArray'];
                $value = $transformer($value);
            }

            $instance->{$setter}($value);
        }

        return $instance;
    }

    /**
     * Find all.
     *
     * @param PDO $db Database connection.
     *
     * @return Generator
     */
    public static function findAll(PDO $db)
    {
        return static::findBy($db);
    }

    /**
     * Find multiple records using a simple WHERE clause.
     *
     * @param PDO   $db    Database connection.
     * @param array $where Column => value map to search on.
     *
     * @return Generator
     */
    public static function findBy(PDO $db, array $where = [])
    {
        $stmt = (new QueryBuilder($db, static::class))
            ->where($where)
            ->execute()
        ;

        while (($row = $stmt->fetch())) {
            yield static::createFromArray($row, $db);
        }
    }

    /**
     * Find by id, and return an initialised instance.
     *
     * @param PDO $db Database connection.
     * @param int $id Primary key value.
     *
     * @return AbstractModel|false
     */
    public static function findById(PDO $db, $id)
    {
        $stmt = (new QueryBuilder($db, static::class))
            ->where([
                'id' => $id,
            ])
            ->limit(1)
            ->execute()
        ;

        if (($row = $stmt->fetch())) {
            return static::createFromArray($row, $db);
        }

        return false;
    }

    /**
     * Get the table name for this model.
     *
     * @return string
     */
    public static function getTableName()
    {
        throw new \Exception('getTableName not overridden in ' . get_called_class());
    }

    public function __construct(PDO $db = null)
    {
        $this->db = $db;
    }

    /**
     * Getter for primary key value.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Getter for deleted.
     *
     * @return bool
     */
    public function isDeleted()
    {
        return $this->deleted;
    }

    /**
     * Map properties back to columns, and apply reverse transformers.
     *
     * @return array
     */
    public function toArray()
    {
        $data = [];

        $columns = static::$columns;
        $map = static::$columnMap;
        $transformers = static::$valueTransformers;

        foreach ($columns as $column) {
            if (array_key_exists($column, $map)) {
                $getter = 'get' . ucfirst($map[$column]);
            } else {
                $getter = 'get' . ucfirst($column);
            }

            $value = $this->{$getter}();

            // Don't return an 'id' column if null.
            if ($value === null && $column == 'id') {
                continue;
            }

            if (array_key_exists($column, $transformers)) {
                $transformer = [$transformers[$column], 'toArray'];
                $value = $transformer($value);
            }

            $data[$column] = $value;
        }

        return $data;
    }

    /**
     * Save the model to storage.
     *
     * @param PDO $db Optional PDO instance.
     *
     * @return $this
     */
    public function save(PDO $db = null)
    {
        if ($db === null) {
            $db = $this->db;
        }

        if ($db === null) {
            throw new \Exception('Cannot save without a database connection.');
        }

        if ($this->deleted) {
            throw new \Exception('Cannot save a deleted ' . get_class($this));
        }

        if ($this->getId() === null) {
            $this->create($db);
        } else {
            $this->update($db);
        }

        return $this;
    }

    /**
     * Delete the model from storage.
     *
     * @param PDO $db Optional PDO instance.
     *
     * @return $this
     */
    public function delete(PDO $db = null)
    {
        if ($this->deleted) {
            return;
        }

        if ($db === null) {
            $db = $this->db;
        }

        if ($db === null) {
            throw new \Exception('Cannot delete without a database connection.');
        }

        if ($this->getId() === null) {
            throw new \Exception('Cannot delete a nonexistent ' . get_class($this));
        }

        $stmt = $db->prepare(
            sprintf(
                'DELETE FROM `%1$s` WHERE `%1$s`.`id` = :id',
                static::getTableName()
            )
        );

        $stmt->execute(['id' => $this->getId()]);

        $this->deleted = true;

        return $this;
    }

    protected function create(PDO $db)
    {
        $data = $this->toArray();

        $columns = [];
        foreach (array_keys($data) as $column) {
            $columns[] = '`' . $column . '` = :' . $column;
        }

        $stmt = $db->prepare(
            sprintf(
                'INSERT INTO
                    `%s`
                SET
                    %s',
                static::getTableName(),
                implode(', ', $columns)
            )
        );

        $stmt->execute($data);

        $this->setId($db->lastInsertId());
    }

    protected function update(PDO $db)
    {
        $data = $this->toArray();

        $columns = [];
        foreach (array_keys($data) as $column) {
            if ($column == 'id') {
                continue;
            }
            $columns[] = '`' . $column . '` = :' . $column;
        }

        $stmt = $db->prepare(
            sprintf(
                'UPDATE
                    `%s`
                SET
                    %s
                WHERE
                    id = :id
                LIMIT
                    1',
                static::getTableName(),
                implode(', ', $columns)
            )
        );

        $stmt->execute($data);
    }

    /**
     * Setter for primary key value.
     *
     * @return $this
     */
    protected function setId($id)
    {
        if ($this->id !== null) {
            throw new \Exception('Cannot change the primary key of ' . get_class($this));
        }

        $this->id = $id;
        return $this;
    }
}
