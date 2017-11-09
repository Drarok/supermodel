<?php

namespace Zerifas\Supermodel\Test;

use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;
use Zerifas\Supermodel\Cache\MemoryCache;
use Zerifas\Supermodel\Connection;
use Zerifas\Supermodel\QueryBuilder;
use Zerifas\Supermodel\Test\Model\PostModel;

class ConnectionTest extends TestCase
{
    /**
     * @var PDO|PHPUnit_Framework_MockObject_MockObject
     */
    protected $pdo;

    /**
     * @var PDOStatement|PHPUnit_Framework_MockObject_MockObject
     */
    protected $stmt;

    /**
     * @var Connection
     */
    protected $conn;

    public function setUp()
    {
        parent::setUp();

        $this->stmt = $this->getMockBuilder(\PDOStatement::class)
            ->disableOriginalConstructor()
            ->disableProxyingToOriginalMethods()
            ->getMock();

        $this->pdo = $this->getMockBuilder(\PDO::class)
            ->disableOriginalConstructor()
            ->disableProxyingToOriginalMethods()
            ->getMock();

        $this->pdo->expects($this->any())
            ->method('prepare')
            ->willReturn($this->stmt);

        $this->conn = new Connection('', '', '', new MemoryCache(), $this->pdo);
    }

    public function testConstructorWithDSN()
    {
        $dsn = 'sqlite::memory:';
        $conn = new Connection($dsn, '', '', new MemoryCache());
        $stmt = $conn->prepare('SELECT 1');
        $this->assertInstanceOf(PDOStatement::class, $stmt);
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
        $stmt = $this->conn->prepare('SELECT 1');
        $this->assertInstanceOf(PDOStatement::class, $stmt);
    }

    public function testSaveCreate()
    {
        /** @var PHPUnit_Framework_MockObject_MockObject|Connection $mockedConn */
        $mockedConn = $this->getMockBuilder(Connection::class)
            ->enableOriginalConstructor()
            ->setConstructorArgs(['', '', '', new MemoryCache(), $this->pdo])
            ->disableProxyingToOriginalMethods()
            ->setMethods(['create', 'update'])
            ->getMock();

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
        /** @var PHPUnit_Framework_MockObject_MockObject|Connection $mockedConn */
        $mockedConn = $this->getMockBuilder(Connection::class)
            ->enableOriginalConstructor()
            ->setConstructorArgs(['', '', '', new MemoryCache(), $this->pdo])
            ->disableProxyingToOriginalMethods()
            ->setMethods(['create', 'update'])
            ->getMock();

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
        $sql = 'INSERT INTO `posts` (`createdAt`, `updatedAt`, `authorId`, `userId`, `title`, `body`) '
            . 'VALUES (?, ?, ?, ?, ?, ?)';

        $this->pdo->expects($this->once())
            ->method('prepare')
            ->with($sql);

        $this->pdo->expects($this->once())
            ->method('lastInsertId')
            ->willReturn(1);

        $now = (new \DateTime())->format('Y-m-d H:i:s');

        $this->stmt->expects($this->once())
            ->method('execute')
            ->with([$now, $now, null, null, 'title 1', 'body 1'])
            ->willReturn(true);

        $post = new PostModel();
        $post->setTitle('title 1');
        $post->setBody('body 1');
        $this->conn->save($post);

        $this->assertEquals(1, $post->getId());
    }

    public function testUpdate()
    {
        $sql = 'UPDATE `posts` SET '
            . '`createdAt` = ?, `updatedAt` = ?, `authorId` = ?, `userId` = ?, `title` = ?, `body` = ? '
            . 'WHERE id = ?';

        $this->pdo->expects($this->once())
            ->method('prepare')
            ->with($sql);

        $this->pdo->expects($this->never())
            ->method('lastInsertId');

        $then = '2017-09-05 15:47:48';
        $now = (new \DateTime())->format('Y-m-d H:i:s');

        $this->stmt->expects($this->once())
            ->method('execute')
            ->with([$then, $now, null, null, 'title 2', 'body 2', 10])
            ->willReturn(true);

        $post = new PostModel();
        $post->setId(10);
        $post->setCreatedAt(\DateTime::createFromFormat('Y-m-d H:i:s', $then));
        $post->setTitle('title 2');
        $post->setBody('body 2');
        $this->conn->save($post);

        $this->assertEquals(10, $post->getId());
    }

    public function testDelete()
    {
        $sql = 'DELETE FROM `posts` WHERE `id` = ?';

        $this->pdo->expects($this->once())
            ->method('prepare')
            ->with($sql);

        $this->stmt->expects($this->once())
            ->method('execute')
            ->with([10])
            ->willReturn(true);

        $post = new PostModel();
        $post->setId(10);
        $this->assertTrue($this->conn->delete($post));
    }

    public function testDeleteAll()
    {
        $sql = 'DELETE FROM `posts` WHERE `id` = ?';

        $this->pdo->expects($this->exactly(2))
            ->method('prepare')
            ->with($sql);

        $this->stmt->expects($this->exactly(2))
            ->method('execute')
            ->withConsecutive([[10]], [[11]])
            ->willReturn(true);

        $models = [
            (new PostModel())->setId(10),
            (new PostModel())->setId(11),
        ];

        $this->assertTrue($this->conn->deleteAll($models));
    }

    public function testDeleteAllWithFailure()
    {
        $sql = 'DELETE FROM `posts` WHERE `id` = ?';

        $this->pdo->expects($this->exactly(2))
            ->method('prepare')
            ->with($sql);

        $this->pdo->expects($this->once())
            ->method('rollback');

        $this->stmt->expects($this->exactly(2))
            ->method('execute')
            ->withConsecutive([[10]], [[11]])
            ->willReturnOnConsecutiveCalls(true, false);

        $models = [
            (new PostModel())->setId(10),
            (new PostModel())->setId(11),
        ];

        $this->assertFalse($this->conn->deleteAll($models));
    }
}
