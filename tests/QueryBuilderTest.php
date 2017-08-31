<?php

namespace Zerifas\Supermodel\Test;

use Doctrine\Instantiator\Exception\InvalidArgumentException;
use PDOStatement;
use PHPUnit\Framework\TestCase;

use PHPUnit_Framework_MockObject_MockObject;
use Zerifas\Supermodel\Cache\MemoryCache;
use Zerifas\Supermodel\Connection;
use Zerifas\Supermodel\Metadata\MetadataCache;
use Zerifas\Supermodel\QueryBuilder;
use Zerifas\Supermodel\Test\Model\PostModel;
use Zerifas\Supermodel\Test\Model\UserModel;

class QueryBuilderTest extends TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|Connection
     */
    protected $conn;

    /**
     * @var QueryBuilder
     */
    protected $qb;

    public function setUp()
    {
        parent::setUp();

        /** @var PHPUnit_Framework_MockObject_MockObject $mockConn */
        $mockConn = $this->createMock(Connection::class);
        $mockConn
            ->method('getMetadata')
            ->willReturn(new MetadataCache(new MemoryCache()));

        $this->conn = $mockConn;
        $this->qb = new QueryBuilder($this->conn, PostModel::class, 'p');
    }

    public function testSimpleQuery()
    {
        $sql = 'SELECT * FROM `posts` AS `p`';

        $stmt = $this->createMock('PDOStatement');
        $stmt->expects($this->exactly(2))
            ->method('fetch')
            ->willReturn(
                [
                    'p.id' => 10,
                ],
                false
            )
        ;

        $this->conn
            ->expects($this->once())
            ->method('prepare')
            ->with($sql)
            ->willReturn($stmt)
        ;

        $result = $this->qb
            ->fetchAll()
        ;

        $count = 0;
        foreach ($result as $post) {
            $this->assertEquals(10, $post->getId());
            ++$count;
        }
        $this->assertEquals(1, $count);
    }

    public function testSimpleJoinQuery()
    {
        $sql = implode(' ', [
            'SELECT * FROM `posts` AS `p`',
            'INNER JOIN `users` AS `a` ON `a`.`id` = `p`.`authorId`',
            'INNER JOIN `users` AS `u` ON `u`.`id` = `p`.`userId`',
            'WHERE `p`.`id` = ?',
            'LIMIT 1',
        ]);

        $this->conn
            ->expects($this->once())
            ->method('prepare')
            ->with($sql)
            ->willReturn(new \PDOStatement())
        ;

        $this->qb
            ->join('author', 'a')
            ->join('user', 'u')
            ->byId(10)
            ->fetchOne()
        ;
    }

    public function testJoinWithWhereQuery()
    {
        $sql = implode(' ', [
            'SELECT * FROM `posts` AS `p`',
            'INNER JOIN `users` AS `a` ON `a`.`id` = `p`.`authorId`',
            'INNER JOIN `users` AS `u` ON `u`.`id` = `p`.`userId`',
            'WHERE `p`.`id` = ?',
            'AND `p`.`createdAt` > ?',
            'AND `a`.`enabled` = ?',
            'AND `u`.`enabled` != ?',
            'AND `p`.`id` < ?',
            'AND `p`.`id` > ?',
            'AND `p`.`id` <= ?',
            'AND `p`.`id` >= ?',
            'AND `p`.`id` IS NULL',
            'AND `p`.`id` IS NOT NULL',
            'LIMIT 1',
        ]);

        $datetime = \DateTime::createFromFormat('Y-m-d H:i:s', '2017-01-01 00:00:00');

        $stmt = $this->createMock('PDOStatement');
        $stmt->expects($this->once())
            ->method('execute')
            ->with([1, '2017-01-01 00:00:00', 1, 0, 10, 10, 10, 10])
        ;

        $this->conn
            ->expects($this->once())
            ->method('prepare')
            ->with($sql)
            ->willReturn($stmt)
        ;

        $this->qb
            ->join('author', 'a')
            ->join('user', 'u')
            ->where('p.id = ?', 1)
            ->where('p.createdAt > ?', $datetime)
            ->where('a.enabled = ?', true)
            ->where('u.enabled != ?', false)
            ->where('p.id < ?', 10)
            ->where('p.id > ?', 10)
            ->where('p.id <= ?', 10)
            ->where('p.id >= ?', 10)
            ->where('p.id IS NULL')
            ->where('p.id IS NOT NULL')
            ->fetchOne()
        ;
    }

    public function testJoinFailsWithInvalidRelationName()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage(
            'no-such-relation is not a relation of Zerifas\\Supermodel\\Test\\Model\\PostModel'
        );

        $this->qb
            ->join('no-such-relation', 'n')
        ;
    }

    public function testOrderBy()
    {
        $sql = 'SELECT * FROM `posts` AS `p` '
            . 'WHERE `p`.`id` = ? '
            . 'ORDER BY `p`.`createdAt` DESC LIMIT 1';

        $this->conn
            ->expects($this->once())
            ->method('prepare')
            ->with($sql)
            ->willReturn(new \PDOStatement())
        ;

        $this->qb
            ->orderBy('p.createdAt', 'DESC')
            ->byId(10)
            ->fetchOne()
        ;
    }

    public function testLimitAndOffset()
    {
        $sql = 'SELECT * FROM `posts` AS `p` LIMIT 10 OFFSET 15';

        $this->conn
            ->expects($this->once())
            ->method('prepare')
            ->with($sql)
            ->willReturn(new \PDOStatement())
        ;

        $result = $this->qb
            ->limit(10)
            ->offset(15)
            ->fetchAll()
        ;

        // Force the Generator to run.
        iterator_count($result);
    }
}
