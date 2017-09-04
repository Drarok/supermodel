<?php

namespace Zerifas\Supermodel\Test\Model;

use Zerifas\Supermodel\AutoAccessorsTrait;
use Zerifas\Supermodel\Model;
use Zerifas\Supermodel\Relation\ManyToManyRelation;

class TagModel extends Model
{
    use AutoAccessorsTrait;

    protected $name;
    protected $posts;

    public static function getTableName(): string
    {
        return 'tags';
    }

    public static function getColumns(): array
    {
        return [
            'id',
            'name',
        ];
    }

    public static function getValueTransformers(): array
    {
        return [];
    }

    public static function getRelations(): array
    {
        return [
            'posts' => new ManyToManyRelation(PostModel::class, 'posts_tags', 'tagId', 'postId'),
        ];
    }
}
