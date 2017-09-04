<?php

namespace Zerifas\Supermodel\Test;

use DateTime;

use Doctrine\Instantiator\Exception\InvalidArgumentException;
use PHPUnit\Framework\TestCase;

use Zerifas\Supermodel\Cache\MemoryCache;
use Zerifas\Supermodel\Metadata\MetadataCache;
use Zerifas\Supermodel\Test\Model\InvalidModel;
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

    public function testSimpleCreate()
    {
        $date = '2016-01-01 00:00:00';

        $data = [
            'p.id' => 1,
            'p.createdAt' => $date,
            'p.updatedAt' => $date,
            'p.userId' => 1,
            'p.title' => 'This is a title',
            'p.body' => 'This is a body',
            'p.enabled' => 1,
        ];

        $obj = PostModel::createFromArray($data, $this->metadata, 'p');

        $this->assertAttributeEquals(1, 'id', $obj);
        $this->assertAttributeEquals(DateTime::createFromFormat('Y-m-d H:i:s', $date), 'createdAt', $obj);

        $this->assertEquals('This is a title', $obj->getTitle());
    }

    public function testSimpleCreateWithInvalidRelation()
    {
        $this->expectExceptionMessage('Relation invalid is invalid in Zerifas\\Supermodel\\Test\\Model\\InvalidModel');
        InvalidModel::createFromArray([], $this->metadata, 'p');
    }

    public function testCreateWithBelongsToRelation()
    {
        $date = '2016-01-01 00:00:00';

        $data = [
            'p.id' => 1,
            'p.createdAt' => $date,
            'p.updatedAt' => $date,
            'p.userId' => 1,
            'p.title' => 'This is a title',
            'p.body' => 'This is a body',
            'p.enabled' => 1,

            'user.id' => 2,
            'user.username' => 'drarok',
            'user.enabled' => 1,
        ];

        /** @var PostModel $post */
        $post = PostModel::createFromArray($data, $this->metadata, 'p');

        $this->assertEquals('This is a title', $post->getTitle());

        $user = $post->getUser();
        $this->assertInstanceOf(UserModel::class, $user);
        $this->assertAttributeEquals(2, 'id', $user);
        $this->assertAttributeEquals('drarok', 'username', $user);
        $this->assertAttributeEquals(true, 'enabled', $user);
    }

    public function testCreateWithHasManyRelation()
    {
        $date = '2016-01-01 00:00:00';

        $data = [
            'u.id' => 1,
            'userPosts' => [new PostModel(), new PostModel()],
        ];

        $user = UserModel::createFromArray($data, $this->metadata, 'u');

        $this->assertEquals(1, $user->getId());

        $this->assertCount(2, $user->getUserPosts());
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
