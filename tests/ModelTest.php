<?php

namespace Zerifas\Supermodel\Test;

use DateTime;

use Doctrine\Instantiator\Exception\InvalidArgumentException;
use PHPUnit\Framework\TestCase;

use Zerifas\Supermodel\Cache\MemoryCache;
use Zerifas\Supermodel\Metadata\MetadataCache;
use Zerifas\Supermodel\Test\Model\PostModel;
use Zerifas\Supermodel\Test\Model\UserModel;

class ModelTest extends TestCase
{
    /**
     * @var MetadataCache
     */
    protected $metadata;

    public function setUp()
    {
        parent::setUp();
        $this->metadata = new MetadataCache(new MemoryCache());
    }

    public function testCreateWithoutRelations()
    {
        $date = '2016-01-01 00:00:00';

        $data = [
            'posts.id' => 1,
            'posts.createdAt' => $date,
            'posts.updatedAt' => $date,
            'posts.userId' => 1,
            'posts.title' => 'This is a title',
            'posts.body' => 'This is a body',
            'posts.enabled' => 1,
        ];

        $obj = PostModel::createFromArray($data, $this->metadata, 'posts');

        $this->assertAttributeEquals(1, 'id', $obj);
        $this->assertAttributeEquals(DateTime::createFromFormat('Y-m-d H:i:s', $date), 'createdAt', $obj);

        $this->assertEquals('This is a title', $obj->getTitle());
    }

    public function testCreateWithSingleRelation()
    {
        $date = '2016-01-01 00:00:00';

        $data = [
            'posts.id' => 1,
            'posts.createdAt' => $date,
            'posts.updatedAt' => $date,
            'posts.userId' => 1,
            'posts.title' => 'This is a title',
            'posts.body' => 'This is a body',
            'posts.enabled' => 1,

            'user.id' => 2,
            'user.username' => 'drarok',
            'user.enabled' => 1,
        ];

        $obj = PostModel::createFromArray($data, $this->metadata, 'posts');

        $this->assertEquals('This is a title', $obj->getTitle());

        $user = $obj->getUser();
        $this->assertInstanceOf(UserModel::class, $user);
        $this->assertAttributeEquals(2, 'id', $user);
        $this->assertAttributeEquals('drarok', 'username', $user);
        $this->assertAttributeEquals(true, 'enabled', $user);
    }

    public function testCreateWithManyRelations()
    {
        $date = '2016-01-01 00:00:00';

        $data = [
            'posts.id' => 1,
            'posts.createdAt' => $date,
            'posts.updatedAt' => $date,
            'posts.userId' => 1,
            'posts.title' => 'This is a title',
            'posts.body' => 'This is a body',
            'posts.enabled' => 1,

            'author.id' => 5,
            'author.username' => 'alice',
            'author.enabled' => 0,

            'user.id' => 2,
            'user.username' => 'drarok',
            'user.enabled' => 1,
        ];

        $obj = PostModel::createFromArray($data, $this->metadata, 'posts');

        $this->assertEquals('This is a title', $obj->getTitle());

        $author = $obj->getAuthor();
        $this->assertInstanceOf(UserModel::class, $author);
        $this->assertAttributeEquals(5, 'id', $author);
        $this->assertAttributeEquals('alice', 'username', $author);
        $this->assertAttributeEquals(false, 'enabled', $author);

        $user = $obj->getUser();
        $this->assertInstanceOf(UserModel::class, $user);
        $this->assertAttributeEquals(2, 'id', $user);
        $this->assertAttributeEquals('drarok', 'username', $user);
        $this->assertAttributeEquals(true, 'enabled', $user);
    }

    public function testToArray()
    {
        $date = '2016-01-01 00:00:00';
        $data = [
            'posts.id' => 1,
            'posts.createdAt' => $date,
            'posts.updatedAt' => $date,
            'posts.authorId' => 1,
            'posts.userId' => 1,
            'posts.title' => 'This is a title',
            'posts.body' => 'This is a body',
            'posts.enabled' => 1,

            'author.id' => 5,
            'author.username' => 'alice',
            'author.enabled' => 0,

            'user.id' => 2,
            'user.username' => 'drarok',
            'user.enabled' => 1,
        ];

        $post = PostModel::createFromArray($data, $this->metadata, 'posts');
        $actual = $post->toArray($this->metadata);
        $now = (new \DateTime())->format('Y-m-d H:i:s');

        $expected = [
            'posts.id' => 1,
            'posts.createdAt' => $date,
            'posts.updatedAt' => $now,
            'posts.authorId' => 1,
            'posts.userId' => 1,
            'posts.title' => 'This is a title',
            'posts.body' => 'This is a body',
            'posts.enabled' => 1,
        ];
        $this->assertEquals($expected, $actual);
    }

    public function testSetPrimaryKey()
    {
        $post = new PostModel();
        $post->setId(1);

        $this->assertEquals(1, $post->getId());
    }

    public function testChangePrimaryKeyException()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('You cannot change the primary key of an existing object.');

        $data = [
            'posts.id' => 1,
        ];
        $post = PostModel::createFromArray($data, $this->metadata, 'posts');
        $post->setId(2);
    }
}
