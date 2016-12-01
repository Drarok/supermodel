<?php

namespace Zerifas\Supermodel\Test;

use PHPUnit\Framework\TestCase;
use Zerifas\Supermodel\Cache\MemoryCache;
use Zerifas\Supermodel\Connection;
use Zerifas\Supermodel\Test\Model\PostModel;

class ConnectionTest extends TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|Connection
     */
    protected $conn;

    public function setUp()
    {
        parent::setUp();

        $this->conn = new Connection('sqlite::memory:', '', '', new MemoryCache());
        $mock = $this->getMockBuilder(Connection::class)
            ->setConstructorArgs(['sqlite::memory:', '', '', new MemoryCache()])
            ->setMethods(null)
            ->getMock()
        ;

        $create = implode(' ', [
            'CREATE TABLE "posts"',
            '(id INTEGER PRIMARY KEY,',
            'createdAt TEXT,',
            'updatedAt TEXT,',
            'authorId INTEGER,',
            'userId INTEGER,',
            'title TEXT,',
            'body TEXT,',
            'enabled INTEGER)',
        ]);

        $insert = implode(' ', [
            'INSERT INTO "posts"',
            '(id, createdAt, updatedAt, authorId,',
            'userId, title, body, enabled)',
            'VALUES(?, ?, ?, ?, ?, ?, ?, ?)',
        ]);

        $this->conn->prepare($create)->execute();
        $this->conn->prepare($insert)->execute([
            1,
            '2016-01-01 00:00:00',
            '2016-01-01 00:00:00',
            1,
            2,
            'This is a sample title',
            'This is a sample body',
            1,
        ]);
    }

    public function testFind()
    {
        // This object won't contain any data because the SQLite driver doesn't support PDO::ATTR_FETCH_TABLE_NAMES
        $post = $this->conn->find(PostModel::class)
            ->byId(1)
            ->fetchOne()
        ;

        $this->assertNotFalse($post);
        $this->assertInstanceOf(PostModel::class, $post);
    }

    public function testSaveCreate()
    {
        $post = new PostModel();
        $post->title = 'This is a sample title!!!';
        $this->conn->save($post);

        $stmt = $this->conn->prepare('SELECT COUNT(*) FROM posts');
        $stmt->execute();

        // There's already 1 row in the table from setUp, so now there should be 2.
        $this->assertEquals(2, $stmt->fetchColumn());
    }

    public function testSaveUpdate()
    {
        $post = new PostModel();
        $post->setId(1);
        $post->title = 'This is a sample title!!!';
        $this->conn->save($post);

        $stmt = $this->conn->prepare('SELECT COUNT(*) FROM posts');
        $stmt->execute();

        // We should have updated the existing row.
        $stmt = $this->conn->prepare('SELECT * FROM posts');
        $stmt->execute();
        $rows = $stmt->fetchAll();

        $this->assertCount(1, $rows);
        $this->assertEquals($post->title, $rows[0]['title']);
    }

    public function testSaveAll()
    {
        $posts = [];
        $posts[] = (new PostModel())->setTitle('Created 1');
        $posts[] = (new PostModel())->setTitle('Created 2');

        $this->conn->saveAll($posts);

        $stmt = $this->conn->prepare('SELECT * FROM posts');
        $stmt->execute();
        $rows = $stmt->fetchAll();

        $this->assertCount(3, $rows);
        $this->assertEquals('This is a sample title', $rows[0]['title']);
        $this->assertEquals('Created 1', $rows[1]['title']);
        $this->assertEquals('Created 2', $rows[2]['title']);
    }
}
