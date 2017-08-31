<?php

namespace Zerifas\Supermodel\Test\Model;

use Zerifas\Supermodel\AutoAccessorsTrait;
use Zerifas\Supermodel\Relation\BelongsToRelation;
use Zerifas\Supermodel\TimestampedModel;
use Zerifas\Supermodel\Transformers\BooleanTransformer;

class PostModel extends TimestampedModel
{
    use AutoAccessorsTrait;

    protected $authorId;
    protected $userId;
    protected $title;
    protected $body;
    protected $enabled;

    protected $author;
    protected $user;

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
            'enabled',
        ];
    }

    public static function getRelations(): array
    {
        return [
            'author' => new BelongsToRelation(UserModel::class, 'authorId'),
            'user'   => new BelongsToRelation(UserModel::class, 'userId'),
        ];
    }

    public static function getValueTransformers(): array
    {
        return array_merge(parent::getValueTransformers(), [
            'enabled' => BooleanTransformer::class,
        ]);
    }
}
