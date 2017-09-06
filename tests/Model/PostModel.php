<?php

namespace Zerifas\Supermodel\Test\Model;

use Zerifas\Supermodel\AutoAccessorsTrait;
use Zerifas\Supermodel\Relation\BelongsToRelation;
use Zerifas\Supermodel\Relation\ManyToManyRelation;
use Zerifas\Supermodel\TimestampedModel;
use Zerifas\Supermodel\Transformer\BooleanTransformer;

class PostModel extends TimestampedModel
{
    use AutoAccessorsTrait;

    protected $authorId;
    protected $userId;
    protected $title;
    protected $body;

    protected $author;
    protected $user;
    protected $tags;

    public static function getTableName(): string
    {
        return 'posts';
    }

    public static function getColumns(): array
    {
        return [
            'id',
            'createdAt',
            'updatedAt',
            'authorId',
            'userId',
            'title',
            'body',
        ];
    }

    public static function getRelations(): array
    {
        return [
            'author' => new BelongsToRelation(UserModel::class, 'authorId'),
            'user'   => new BelongsToRelation(UserModel::class, 'userId'),
            'tags'   => new ManyToManyRelation(TagModel::class, 'posts_tags', 'postId', 'tagId'),
        ];
    }

    public static function getValueTransformers(): array
    {
        return array_merge(parent::getValueTransformers(), [
            'enabled' => BooleanTransformer::class,
        ]);
    }

    /**
     * @return UserModel|null
     */
    public function getUser()
    {
        return $this->user;
    }
}
