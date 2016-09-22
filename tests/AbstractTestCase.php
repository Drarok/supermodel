<?php

namespace Zerifas\Supermodel\Test;

use PHPUnit_Framework_TestCase;

use Zerifas\Supermodel\QueryBuilder;

abstract class AbstractTestCase extends PHPUnit_Framework_TestCase
{
    protected $db;

    protected $qb;

    public function setUp()
    {
        parent::setUp();

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_STRINGIFY_FETCHES  => false,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_STATEMENT_CLASS    => ['Zerifas\\Supermodel\\Test\\PDOStatement'],
        ];
        $this->db = new PDO('sqlite::memory:', '', '', $options);
        $this->qb = new QueryBuilder($this->db);
    }
}
