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
        $sql = 'SELECT `p`.* FROM `posts` AS `p`';
        $sql = 'SELECT `p`.* FROM `posts` AS `p`';

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
            'SELECT `p`.* FROM `posts` AS `p`',
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
            'SELECT `p`.* FROM `posts` AS `p`',
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
            'AND `p`.`id` IN (?, ?, ?)',
            'AND `p`.`id` NOT IN (?, ?, ?)',
            'AND `p`.`id` BETWEEN ? AND ?',
            'LIMIT 1',
        ]);

        $datetime = \DateTime::createFromFormat('Y-m-d H:i:s', '2017-01-01 00:00:00');

        $stmt = $this->createMock('PDOStatement');
        $stmt->expects($this->once())
            ->method('execute')
            ->with([1, '2017-01-01 00:00:00', 1, 0, 10, 10, 10, 10, 3, 2, 1, 7, 8, 9, 100, 1000])
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
            ->where('p.id IN ?', 3, 2, 1)
            ->where('p.id NOT IN ?', 7, 8, 9)
            ->where('p.id BETWEEN ? AND ?', 100, 1000)
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
        $sql = 'SELECT `p`.* FROM `posts` AS `p` '
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
        $sql = 'SELECT `p`.* FROM `posts` AS `p` LIMIT 10 OFFSET 15';

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

    public function testHasManyRelation()
    {
        $sql1 = 'SELECT `u`.*, GROUP_CONCAT(`up`.`id`) AS `userPosts` FROM `users` AS `u` '
            . 'LEFT OUTER JOIN `posts` AS `up` ON `up`.`userId` = `u`.`id` '
            . 'WHERE `u`.`enabled` = ? '
            . 'GROUP BY `u`.`id` '
            . 'LIMIT 1'
        ;

        $sql2 = 'SELECT `userPosts`.* FROM `posts` AS `userPosts` WHERE `userPosts`.`id` IN (?, ?)';

        $data1 = [
            'u.id' => 1,
            'u.username' => 'drarok',
            'u.enabled' => 1,
            'userPosts' => '3,5',
        ];

        $data2 = [
            'userPosts.id' => 3,
        ];

        $data3 = [
            'userPosts.id' => 5,
        ];

        $stmt1 = $this->createMock('PDOStatement');
        $stmt1->expects($this->once())
            ->method('execute')
            ->with([1])
        ;
        $stmt1->expects($this->once())
            ->method('fetch')
            ->willReturn($data1)
        ;

        $stmt2 = $this->createMock('PDOStatement');
        $stmt2->expects($this->once())
            ->method('execute')
            ->with([3, 5])
        ;
        $stmt2->expects($this->exactly(3))
            ->method('fetch')
            ->willReturnOnConsecutiveCalls($data2, $data3, false)
        ;

        $this->conn
            ->expects($this->exactly(2))
            ->method('prepare')
            ->withConsecutive([$sql1], [$sql2])
            ->willReturnOnConsecutiveCalls($stmt1, $stmt2)
        ;

        /** @var UserModel $result */
        $result  = (new QueryBuilder($this->conn, UserModel::class, 'u'))
            ->join('userPosts', 'up')
            ->where('u.enabled = ?', true)
            ->fetchOne()
        ;

        $this->assertInstanceOf(UserModel::class, $result);
        $this->assertCount(2, $result->getUserPosts());

        $map = function (PostModel $post) {
            return $post->getId();
        };
        $postIds = array_map($map, $result->getUserPosts());
        $this->assertEquals([3, 5], $postIds);
    }
}
