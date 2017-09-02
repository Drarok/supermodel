<?php

namespace Zerifas\Supermodel\Test\Model;

use Zerifas\Supermodel\AutoAccessorsTrait;
use Zerifas\Supermodel\Connection;
use Zerifas\Supermodel\Model;
use Zerifas\Supermodel\Relation\HasManyRelation;
use Zerifas\Supermodel\Transformers\BooleanTransformer;

class UserModel extends Model
{
    use AutoAccessorsTrait;

    protected $username;
    protected $enabled;

    /**
     * @var PostModel[]
     */
    protected $userPosts;

    /**
     * @var PostModel[]
     */
    protected $authorPosts;

    public static function getTableName(): string
    {
        return 'users';
    }

    public static function getColumns(): array
    {
        return [
            'id',
            'username',
            'enabled',
        ];
    }

    public static function getRelations(): array
    {
        return [
            'userPosts' => new HasManyRelation(PostModel::class, 'userId'),
            'authorPosts' => new HasManyRelation(PostModel::class, 'authorId'),
        ];
    }

    public static function getValueTransformers(): array
    {
        return [
            'enabled' => BooleanTransformer::class,
        ];
    }

    /**
     * @return PostModel[]
     */
    public function getUserPosts(): array
    {
        return $this->userPosts;
    }

    /**
     * @return PostModel[]
     */
    public function getAuthorPosts(): array
    {
        return $this->authorPosts;
    }
}
