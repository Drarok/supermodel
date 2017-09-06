<?php

namespace Zerifas\Supermodel\Test\Model;

use Zerifas\Supermodel\AutoAccessorsTrait;
use Zerifas\Supermodel\Model;
use Zerifas\Supermodel\Relation\HasManyRelation;
use Zerifas\Supermodel\Transformer\BooleanTransformer;

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
     * @return PostModel[]|null
     */
    public function getUserPosts()
    {
        return $this->userPosts;
    }

    /**
     * @return PostModel[]|null
     */
    public function getAuthorPosts()
    {
        return $this->authorPosts;
    }
}
