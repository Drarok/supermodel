<?php

namespace Zerifas\Supermodel\Test;

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
     * @var PHPUnit_Framework_MockObject_MockObject|Connection
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
            ->expects($this->any())
            ->method('getMetadata')
            ->willReturn(new MetadataCache(new MemoryCache()));

        $this->conn = $mockConn;
        $this->qb = new QueryBuilder($this->conn, PostModel::class, 'p');
    }

    public function testFetchOneValid()
    {
        $sql = $params = $data = [];
        $sql[] = 'SELECT `posts`.* FROM `posts` LIMIT 1';
        $params[] = null;
        $data[] = ['posts.id' => 1];

        $this->expectSQLWithParamsToReturnData($sql, $params, $data, ['fetch']);

        $post = $this->qb->fetchOne();
        $this->assertInstanceOf(PostModel::class, $post);
    }

    public function testFetchOneInvalid()
    {
        $this->assertFalse($this->qb->fetchOne());
    }

    public function testSimpleQuery()
    {
        $sql = [];
        $params = [];
        $data = [];

        $sql[] = 'SELECT `posts`.* FROM `posts` WHERE `posts`.`id` > ?';
        $params[] = [0];
        $data[] = [
            ['p.id' => 10]
        ];

        $this->expectSQLWithParamsToReturnData($sql, $params, $data);

        $result = $this->qb
            ->where('p.id > ?', 0)
            ->fetchAll()
        ;
        iterator_count($result);
    }

    public function testSimpleJoinQuery()
    {
        $sql = [];
        $params = [];
        $data = [];

        $sql[] = implode(' ', [
            'SELECT `posts`.*, `author`.*, `user`.* FROM `posts`',
            'INNER JOIN `users` AS `author` ON `author`.`id` = `posts`.`authorId`',
            'INNER JOIN `users` AS `user` ON `user`.`id` = `posts`.`userId`',
            'WHERE `posts`.`id` > ?',
        ]);
        $params[] = [0];

        $data[] = [
            [
                'posts.id' => 1,
                'posts.authorId' => 2,
                'posts.userId' => 3,

                'author.id' => 2,

                'user.id' => 3,
            ],
        ];

        $this->expectSQLWithParamsToReturnData($sql, $params, $data);

        $results = $this->qb
            ->join('author', 'a')
            ->join('user', 'u')
            ->where('p.id > ?', 0)
            ->fetchAll()
        ;

        iterator_count($results);
    }

    public function testDuplicateJoinRelation()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Duplicate join relation: author');

        $this->qb
            ->join('author', 'a')
            ->join('author', 'b');
    }

    public function testDuplicateJoinAlias()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Duplicate join alias: a');

        $this->qb
            ->join('author', 'a')
            ->join('user', 'a');
    }

    public function testJoinWithWhereQuery()
    {
        $sql = [];
        $params = [];
        $data = [];

        $format = 'Y-m-d H:i:s';
        $datetime = \DateTime::createFromFormat($format, '2017-01-01 00:00:00');

        $sql[] = implode(' ', [
            'SELECT `posts`.*, `author`.*, `user`.* FROM `posts`',
            'INNER JOIN `users` AS `author` ON `author`.`id` = `posts`.`authorId`',
            'INNER JOIN `users` AS `user` ON `user`.`id` = `posts`.`userId`',
            'WHERE `posts`.`id` = ?',
            'AND `posts`.`createdAt` > ?',
            'AND `author`.`enabled` = ?',
            'AND `user`.`enabled` != ?',
            'AND `posts`.`id` < ?',
            'AND `posts`.`id` > ?',
            'AND `posts`.`id` <= ?',
            'AND `posts`.`id` >= ?',
            'AND `posts`.`id` IS NULL',
            'AND `posts`.`id` IS NOT NULL',
            'AND `posts`.`id` IN (?, ?, ?)',
            'AND `posts`.`id` NOT IN (?, ?, ?)',
            'AND `posts`.`id` BETWEEN ? AND ?',
        ]);
        $params[] = [1, $datetime->format($format), 1, 0, 10, 10, 10, 10, 3, 2, 1, 7, 8, 9, 100, 1000];
        $data[] = [];

        $this->expectSQLWithParamsToReturnData($sql, $params, $data);

        $results = $this->qb
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
            ->fetchAll()
        ;
        iterator_count($results);
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
        $sql = [];
        $params = [];
        $data = [];

        $sql[] = 'SELECT `posts`.* FROM `posts` '
            . 'WHERE `posts`.`id` > ? '
            . 'ORDER BY `posts`.`createdAt` DESC';
        $params[] = [10];
        $data[] = [];

        $this->expectSQLWithParamsToReturnData($sql, $params, $data);

        $results = $this->qb
            ->orderBy('p.createdAt', 'DESC')
            ->where('p.id > ?', 10)
            ->fetchAll()
        ;
        iterator_count($results);
    }

    public function testLimitAndOffset()
    {
        $sql = [];
        $params = [];
        $data = [];

        $sql[] = 'SELECT `posts`.* FROM `posts` LIMIT 10 OFFSET 15';
        $params[] = null;
        $data[] = [
            ['posts.id' => 1],
        ];

        $this->expectSQLWithParamsToReturnData($sql, $params, $data);

        $results = $this->qb
            ->limit(10)
            ->offset(15)
            ->fetchAll()
        ;
        iterator_count($results);
    }

    public function testBefore()
    {
        $beforeCount = 0;
        $before = function ($sql, $params) use (&$beforeCount) {
            ++$beforeCount;
            $this->assertEquals('SELECT `posts`.* FROM `posts` LIMIT 1', $sql);
            $this->assertEquals(null, $params);
        };

        $this->assertEquals(0, $beforeCount);

        $this->qb
            ->before($before)
            ->fetchOne();

        $this->assertEquals(1, $beforeCount);
    }

    public function testSimpleCount()
    {
        $this->assertEquals(0, $this->qb->count());
    }

    public function testAdvancedCount()
    {
        $date = '2017-09-05 19:25:23';

        $sql = $params = $data = [];

        $sql[] = 'SELECT COUNT(DISTINCT `posts`.`id`) AS `count` FROM `posts` '
            . 'LEFT OUTER JOIN `posts_tags` ON `posts_tags`.`postId` = `posts`.`id` '
            . 'LEFT OUTER JOIN `tags` AS `tags` ON `tags`.`id` = `posts_tags`.`tagId` '
            . 'WHERE `posts`.`createdAt` > ? AND `tags`.`name` = ?';
        $params[] = [$date, 'tag1'];
        $data[] = [1];

        $sql[] = 'SELECT `posts`.*, GROUP_CONCAT(`tags`.`id`) AS `tags` FROM `posts` '
            . 'LEFT OUTER JOIN `posts_tags` ON `posts_tags`.`postId` = `posts`.`id` '
            . 'LEFT OUTER JOIN `tags` AS `tags` ON `tags`.`id` = `posts_tags`.`tagId` '
            . 'WHERE `posts`.`createdAt` > ? AND `tags`.`name` = ? '
            . 'GROUP BY `posts`.`id`';
        $params[] = [$date, 'tag1'];
        $data[] = [
            [
                'posts.id' => 1,
                '.tags' => '2,3',
            ],
        ];

        $sql[] = 'SELECT `tags`.* FROM `tags` WHERE `tags`.`id` IN (?, ?)';
        $params[] = [2, 3];
        $data[] = [
            ['tags.id' => 2],
            ['tags.id' => 3],
        ];

        $this->expectSQLWithParamsToReturnData($sql, $params, $data, ['fetchColumn', 'fetchAll', 'fetchAll']);

        $beforeCount = 0;
        $before = function () use (&$beforeCount) {
            ++$beforeCount;
        };

        $rowCount = $this->qb
            ->before($before)
            ->join('tags', 't')
            ->where('p.createdAt > ?', \DateTime::createFromFormat('Y-m-d H:i:s', $date))
            ->where('t.name = ?', 'tag1')
            ->count();

        $this->assertEquals(1, $rowCount);

        $rows = $this->qb->fetchAll();
        $iterCount = iterator_count($rows);

        $this->assertEquals(1, $iterCount);

        $this->assertEquals(3, $beforeCount);
    }

    public function testInvalidQuery()
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Failed to execute query!');

        $stmt = $this->createMock('PDOStatement');
        $stmt->expects($this->once())
            ->method('fetchAll')
            ->willReturn(false)
        ;
        $this->conn
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($stmt)
        ;

        iterator_count($this->qb->fetchAll());
    }

    public function testHasManyRelation()
    {
        $sql = [];
        $params = [];
        $data = [];

        $sql[] = 'SELECT `users`.*, GROUP_CONCAT(`userPosts`.`id`) AS `userPosts` FROM `users` '
            . 'LEFT OUTER JOIN `posts` AS `userPosts` ON `userPosts`.`userId` = `users`.`id` '
            . 'WHERE `users`.`enabled` = ? '
            . 'GROUP BY `users`.`id`'
        ;
        $params[] = [1];
        $data[] = [
            [
                'users.id' => 1,
                'users.username' => 'drarok',
                'users.enabled' => 1,
                '.userPosts' => '3,5',
            ]
        ];

        $sql[] = 'SELECT `posts`.* FROM `posts` WHERE `posts`.`id` IN (?, ?)';
        $params[] = [3, 5];
        $data[] = [
            ['posts.id' => 3],
            ['posts.id' => 5],
        ];

        $this->expectSQLWithParamsToReturnData($sql, $params, $data);

        $result = (new QueryBuilder($this->conn, UserModel::class, 'u'))
            ->join('userPosts', 'up')
            ->where('u.enabled = ?', true)
            ->fetchAll()
        ;

        iterator_count($result);
    }

    public function testManyToManyJoin()
    {
        $sql = [];
        $params = [];
        $data = [];

        $sql[] = implode(' ', [
            'SELECT `posts`.*, GROUP_CONCAT(`tags`.`id`) AS `tags` FROM `posts`',
            'LEFT OUTER JOIN `posts_tags` ON `posts_tags`.`postId` = `posts`.`id`',
            'LEFT OUTER JOIN `tags` AS `tags` ON `tags`.`id` = `posts_tags`.`tagId`',
            'GROUP BY `posts`.`id`',
        ]);
        $params[] = null;
        $data[] = [
            [
                'posts.id' => 1,
                'posts.createdAt' => '2017-09-04 14:40:02',
                'posts.updatedAt' => '2017-09-04 14:40:02',
                'posts.authorId' => 1,
                'posts.userId' => 2,
                'posts.title' => 'Post title 1',
                'posts.body' => 'Post body 1',
                '.tags' => '1,2',
            ],
        ];


        $sql[] = implode(' ', [
            'SELECT `tags`.* FROM `tags` WHERE `tags`.`id` IN (?, ?)',
        ]);
        $params[] = [1, 2];
        $data[] = [
            [
                'tags.id' => 1,
                'tags.name' => 'tag1',
            ],
            [
                'tags.id' => 2,
                'tags.name' => 'tag2',
            ],
        ];

        $this->expectSQLWithParamsToReturnData($sql, $params, $data);

        $results = (new QueryBuilder($this->conn, PostModel::class, 'p'))
            ->join('tags', 't')
            ->fetchAll()
        ;

        iterator_count($results);
    }

    public function testInvalidCache()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot use partially-filled cache for ' . PostModel::class . '.tags');

        $sql = $params = $data = [];
        $sql[] = 'SELECT `posts`.*, GROUP_CONCAT(`tags`.`id`) AS `tags` FROM `posts` '
            . 'LEFT OUTER JOIN `posts_tags` ON `posts_tags`.`postId` = `posts`.`id` '
            . 'LEFT OUTER JOIN `tags` AS `tags` ON `tags`.`id` = `posts_tags`.`tagId` '
            . 'GROUP BY `posts`.`id`';
        $params[] = null;
        $data[] = [
            [
                'posts.id' => 1,
                '.tags' => '2,3',
            ],
        ];

        $sql[] = 'SELECT `tags`.* FROM `tags` WHERE `tags`.`id` IN (?, ?)';
        $params[] = [2, 3];
        $data[] = [];

        $this->expectSQLWithParamsToReturnData($sql, $params, $data);

        $posts = $this->qb
            ->join('tags', 't')
            ->fetchAll();
        iterator_count($posts);
    }

    private function expectSQLWithParamsToReturnData(
        array $sql,
        array $params,
        array $data,
        array $fetchMethods = null
    ) {
        $map = function ($sql) {
            return [$sql];
        };
        $sql = array_map($map, $sql);

        if ($fetchMethods === null) {
            $fetchMethods = array_fill(0, count($sql), 'fetchAll');
        }

        $statements = [];
        foreach ($sql as $idx => $query) {
            $stmt = $this->createMock('PDOStatement');
            $stmt->expects($this->once())
                ->method('execute')
                ->with($params[$idx])
            ;
            $stmt->expects($this->once())
                ->method($fetchMethods[$idx])
                ->willReturn($data[$idx])
            ;

            $statements[] = $stmt;
        }

        $this->conn
            ->expects($this->exactly(count($sql)))
            ->method('prepare')
            ->withConsecutive(...$sql)
            ->willReturnOnConsecutiveCalls(...$statements)
        ;
    }
}
