<?php

namespace Zerifas\Supermodel;

use PDO;
use PDOStatement;
use Zerifas\Supermodel\Cache\CacheInterface;
use Zerifas\Supermodel\Metadata\MetadataCache;

class Connection
{
    protected $db;
    protected $metadata;

    public function __construct(
        string $dsn,
        string $username,
        string $password,
        CacheInterface $cache,
        PDO $dbOverride = null
    ) {
        if ($dbOverride !== null) {
            $this->db = $dbOverride;
        } else {
            $this->db = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_STRINGIFY_FETCHES => false,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_FETCH_TABLE_NAMES => true,
            ]);

            // This code is MySQL-specific, so cannot be covered by tests.
            $driverName = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
            if ($driverName === 'mysql') {
                $this->db->exec('SET @@group_concat_max_len = 10000000;'); // @codeCoverageIgnore
            }
        }

        $this->metadata = new MetadataCache($cache);
    }

    public function getMetadata(): MetadataCache
    {
        return $this->metadata;
    }

    /**
     * Get a query builder for the given model
     *
     * @param string $class Name of the class
     * @param string $alias Alias to use when specifying clauses
     *
     * @return QueryBuilder
     */
    public function find(string $class, string $alias): QueryBuilder
    {
        return new QueryBuilder($this, $class, $alias);
    }

    /**
     * Prepare SQL on the underlying database
     *
     * @param string $sql Full SQL statement
     *
     * @return PDOStatement
     */
    public function prepare(string $sql): PDOStatement
    {
        return $this->db->prepare($sql);
    }

    /**
     * Save the given model
     *
     * @param Model $obj
     *
     * @return void
     */
    public function save(Model $obj)
    {
        if ($obj->getId() === null) {
            $this->create($obj);
        } else {
            $this->update($obj);
        }
    }

    /**
     * Save an array of objects
     *
     * @param Model[] $objects
     *
     * @return void
     */
    public function saveAll(array $objects)
    {
        $this->db->beginTransaction();

        foreach ($objects as $obj) {
            $this->save($obj);
        }

        $this->db->commit();
    }

    public function delete(Model $obj): bool
    {
        $class = get_class($obj);
        $table = $this->getMetadata()->getTableName($class);

        $sql = "DELETE FROM `$table` WHERE `id` = ?";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$obj->getId()]);
    }

    public function deleteAll(array $objects): bool
    {
        $this->db->beginTransaction();

        foreach ($objects as $obj) {
            if (!$this->delete($obj)) {
                $this->db->rollBack();
                return false;
            }
        }

        $this->db->commit();
        return true;
    }

    /**
     * Create a row in the database from the given model
     *
     * @param Model $obj
     *
     * @return void
     */
    protected function create(Model $obj)
    {
        $data = $obj->toArray($this->metadata);

        $class = get_class($obj);
        $table = $this->metadata->getTableName($class);

        // We don't need the id column when creating, so filter it out.
        $columns = array_filter($this->metadata->getColumns($class), function ($column) {
            return $column !== 'id';
        });

        // Create params by mapping the columns, using null if not set.
        $params = [];
        foreach ($columns as $column) {
            $params[] = $data["$table.$column"] ?? null;
        }

        $placeholders = implode(', ', array_fill(0, count($columns), '?'));
        $columns = '`' . implode('`, `', $columns) . '`';

        $sql = "INSERT INTO `$table` ($columns) VALUES ($placeholders)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $obj->setId($this->db->lastInsertId());

        // TODO: Round-trip the database here to pick up default values.
    }

    /**
     * Update a row in the database from the given model
     *
     * @param Model $obj
     *
     * @return void
     */
    protected function update(Model $obj)
    {
        $data = $obj->toArray($this->metadata);

        $class = get_class($obj);
        $table = $this->metadata->getTableName($class);
        $columns = $this->metadata->getColumns($class);

        $set = [];
        $params = [];
        foreach ($columns as $column) {
            if ($column === 'id') {
                continue;
            }

            $set[] = "`{$column}` = ?";
            $params[] = $data["{$table}.{$column}"] ?? null;
        }
        $params[] = $obj->getId();

        $set = implode(', ', $set);

        $sql = "UPDATE `$table` SET $set WHERE id = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        // TODO: Round-trip the database here.
    }
}
