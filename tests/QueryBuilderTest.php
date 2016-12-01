<?php

namespace Zerifas\Supermodel\Test;

use Doctrine\Instantiator\Exception\InvalidArgumentException;
use PDOStatement;
use PHPUnit\Framework\TestCase;

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
        $this->conn = $this->createMock(Connection::class);
        $metadata = new MetadataCache(new MemoryCache());
        $this->qb = new QueryBuilder($this->conn, PostModel::class, $metadata);
    }

    public function testSimpleQuery()
    {
        $sql = 'SELECT * FROM `posts`';

        $stmt = $this->createMock('PDOStatement');
        $stmt->expects($this->exactly(2))
            ->method('fetch')
            ->willReturn([
                'posts.id' => 10,
            ], false)
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
            'SELECT * FROM `posts`',
            'INNER JOIN `users` AS `author` ON `author`.`id` = `posts`.`authorId`',
            'INNER JOIN `users` AS `user` ON `user`.`id` = `posts`.`userId`',
            'WHERE `posts`.`id` = ?',
            'LIMIT 1',
        ]);

        $this->conn
            ->expects($this->once())
            ->method('prepare')
            ->with($sql)
            ->willReturn(new \PDOStatement())
        ;

        $this->qb
            ->join('author')
            ->join('user')
            ->byId(10)
            ->fetchOne()
        ;
    }

    public function testJoinWithWhereQuery()
    {
        $sql = implode(' ', [
            'SELECT * FROM `posts`',
            'INNER JOIN `users` AS `author` ON `author`.`id` = `posts`.`authorId`',
            'INNER JOIN `users` AS `user` ON `user`.`id` = `posts`.`userId`',
            'WHERE `posts`.`id` = ?',
            'AND `author`.`enabled` = ?',
            'AND `user`.`enabled` != ?',
            'AND `posts`.`id` < ?',
            'AND `posts`.`id` > ?',
            'AND `posts`.`id` <= ?',
            'AND `posts`.`id` >= ?',
            'LIMIT 1',
        ]);

        $this->conn
            ->expects($this->once())
            ->method('prepare')
            ->with($sql)
            ->willReturn(new \PDOStatement())
        ;

        $this->qb
            ->join('author')
            ->join('user')
            ->where([
                PostModel::equal('id', 1),
                PostModel::equal('author.enabled', true),
                PostModel::notEqual('user.enabled', false),
                PostModel::lessThan('id', 10),
                PostModel::greaterThan('id', 0),
                PostModel::lessOrEqual('id', 10),
                PostModel::greaterOrEqual('id', 0),
            ])
            ->fetchOne()
        ;
    }

    public function testJoinFailsWithInvalidRelationName()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage(
            'no-such-relation is not a defined relation of Zerifas\\Supermodel\\Test\\Model\\PostModel'
        );

        $this->qb
            ->join('no-such-relation')
        ;
    }

    public function testOperators()
    {
        $sql = implode(' ', [
            'SELECT * FROM `posts`',
            'WHERE `posts`.`id` > ?',
            'AND `posts`.`title` LIKE ?',
            'AND `posts`.`activationCode` IS NULL',
            'LIMIT 1'
        ]);

        $date = '2016-01-01 00:00:00';

        $stmt = $this->createMock('PDOStatement');
        $stmt
            ->expects($this->once())
            ->method('fetch')
            ->willReturn([
                'posts.id' => 1,
                'posts.createdAt' => $date,
                'posts.updatedAt' => $date,
                'posts.authorId' => 1,
                'posts.userId' => 2,
                'posts.title' => 'This is a sample title',
                'posts.body' => 'This is a sample body',
                'posts.enabled' => 1,
            ])
        ;

        $this->conn
            ->expects($this->once())
            ->method('prepare')
            ->with($sql)
            ->willReturn($stmt)
        ;

        $actual = $this->qb
            ->where([
                PostModel::greaterThan('id', 1),
                PostModel::like('title', 'News%'),
                PostModel::isNull('activationCode'),
            ])
            ->fetchOne()
        ;

        $this->assertInstanceOf(PostModel::class, $actual);
        $this->assertAttributeEquals(1, 'id', $actual);
        $this->assertEquals('This is a sample title', $actual->getTitle());
    }

    public function testOrderBy()
    {
        $sql = 'SELECT * FROM `posts` WHERE `posts`.`id` = ? ORDER BY `posts`.`id` DESC LIMIT 1';

        $this->conn
            ->expects($this->once())
            ->method('prepare')
            ->with($sql)
            ->willReturn(new \PDOStatement())
        ;

        $this->qb
            ->orderBy(PostModel::class, 'id', 'DESC')
            ->byId(10)
            ->fetchOne()
        ;
    }
}
