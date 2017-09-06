<?php

namespace Zerifas\Supermodel\Test;

use PHPUnit\Framework\TestCase;
use Zerifas\Supermodel\Cache\MemoryCache;
use Zerifas\Supermodel\Connection;
use Zerifas\Supermodel\QueryBuilder;
use Zerifas\Supermodel\Test\Model\PostModel;

class ConnectionTest extends TestCase
{
    /**
     * @var PDO
     */
    private $pdo;

    /**
     * @var Connection
     */
    protected $conn;

    public function setUp()
    {
        parent::setUp();

        $stmt = $this->getMockBuilder(\PDOStatement::class)
            ->disableOriginalConstructor()
            ->disableProxyingToOriginalMethods()
            ->getMock();

        $pdo = $this->getMockBuilder(\PDO::class)
            ->disableOriginalConstructor()
            ->disableProxyingToOriginalMethods()
            ->getMock();

        $pdo->expects($this->any())
            ->method('prepare')
            ->willReturn($stmt);

        // TODO: Use mocks instead of a SQLite database.
        $this->conn = new Connection('', '', '', new MemoryCache(), $pdo);
        $this->conn->prepare('CREATE TABLE "posts" ("column" TEXT)')->execute();
    }

    public function testGetMetadata()
    {
        $metadata = $this->conn->getMetadata();
        $this->assertSame($metadata, $this->conn->getMetadata());
    }

    public function testFind()
    {
        $qb = $this->conn->find(PostModel::class, 'p');
        $this->assertInstanceOf(QueryBuilder::class, $qb);
    }

    public function testPrepare()
    {
        $stmt = $this->conn->prepare('SELECT * FROM "posts" WHERE "id" = ?');
        $this->assertInstanceOf(\PDOStatement::class, $stmt);
    }

    public function testSaveCreate()
    {
        $mockedConn = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->disableProxyingToOriginalMethods()
            ->setMethods(['create', 'update'])
            ->getMock();

        $db = $this->createMock(\PDO::class);
        $setupFakes = function () use ($db) {
            $this->db = $db;
        };
        $bound = \Closure::bind($setupFakes, $mockedConn, Connection::class);
        $bound();

        $mockedConn->expects($this->exactly(3))
            ->method('create');
        $mockedConn->expects($this->never())
            ->method('update');

        $model = new PostModel();
        $model->setId(1);
        $mockedConn->save(new PostModel());
        $mockedConn->saveAll([new PostModel(), new PostModel()]);
    }

    public function testSaveUpdate()
    {
        $mockedConn = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->disableProxyingToOriginalMethods()
            ->setMethods(['create', 'update'])
            ->getMock();

        $db = $this->createMock(\PDO::class);
        $setupFakes = function () use ($db) {
            $this->db = $db;
        };
        $bound = \Closure::bind($setupFakes, $mockedConn, Connection::class);
        $bound();

        $mockedConn->expects($this->never())
            ->method('create');
        $mockedConn->expects($this->exactly(3))
            ->method('update');

        $model = new PostModel();
        $model->setId(1);
        $mockedConn->save($model);
        $mockedConn->saveAll([$model, $model]);
    }

    public function testCreate()
    {
        $stmt = $this->getMockBuilder(\PDOStatement::class)
            ->disableOriginalConstructor()
            ->disableProxyingToOriginalMethods()
            ->getMock();

        $db = $this->getMockBuilder(\PDO::class)
            ->disableOriginalConstructor()
            ->disableProxyingToOriginalMethods()
            ->getMock();

        $conn = new Connection('', '', '', new MemoryCache(), $db);

        $sql = 'INSERT INTO `posts` (createdAt, updatedAt, authorId, userId, title, body) '
            . 'VALUES (?, ?, ?, ?, ?, ?)';

        $db->expects($this->once())
            ->method('prepare')
            ->with($sql)
            ->willReturn($stmt);

        $db->expects($this->once())
            ->method('lastInsertId')
            ->willReturn(1);

        $now = (new \DateTime())->format('Y-m-d H:i:s');

        $stmt->expects($this->once())
            ->method('execute')
            ->with([$now, $now, null, null, 'title 1', 'body 1'])
            ->willReturn(true);

        $post = new PostModel();
        $post->setTitle('title 1');
        $post->setBody('body 1');
        $conn->save($post);

        $this->assertEquals(1, $post->getId());
    }

    public function testUpdate()
    {
        $stmt = $this->getMockBuilder(\PDOStatement::class)
            ->disableOriginalConstructor()
            ->disableProxyingToOriginalMethods()
            ->getMock();

        $db = $this->getMockBuilder(\PDO::class)
            ->disableOriginalConstructor()
            ->disableProxyingToOriginalMethods()
            ->getMock();

        $conn = new Connection('', '', '', new MemoryCache(), $db);

        $sql = 'UPDATE `posts` SET createdAt = ?, updatedAt = ?, authorId = ?, userId = ?, title = ?, body = ? '
            . 'WHERE id = ?';

        $db->expects($this->once())
            ->method('prepare')
            ->with($sql)
            ->willReturn($stmt);

        $db->expects($this->never())
            ->method('lastInsertId');

        $then = '2017-09-05 15:47:48';
        $now = (new \DateTime())->format('Y-m-d H:i:s');

        $stmt->expects($this->once())
            ->method('execute')
            ->with([$then, $now, null, null, 'title 2', 'body 2', 10])
            ->willReturn(true);

        $post = new PostModel();
        $post->setId(10);
        $post->setCreatedAt(\DateTime::createFromFormat('Y-m-d H:i:s', $then));
        $post->setTitle('title 2');
        $post->setBody('body 2');
        $conn->save($post);

        $this->assertEquals(10, $post->getId());
    }
}
