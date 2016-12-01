<?php

namespace Zerifas\Supermodel\Test\Model;

use Zerifas\Supermodel\AutoAccessorsTrait;
use Zerifas\Supermodel\Connection;
use Zerifas\Supermodel\Model;
use Zerifas\Supermodel\Transformers\BooleanTransformer;

class UserModel extends Model
{
    use AutoAccessorsTrait;

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

    public static function getValueTransformers(): array
    {
        return [
            'enabled' => BooleanTransformer::class,
        ];
    }

    public static function getRelations(): array
    {
        return [];
    }

    public function getPosts(Connection $conn): Generator
    {
        return $conn
            ->find(PostModel::class)
            ->where([
                PostModel::equal('userId', $this->getId()),
            ])
            ->getResults()
        ;
    }
}
