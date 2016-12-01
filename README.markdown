# Supermodel [![Build Status](https://travis-ci.org/Drarok/supermodel.svg?branch=v2)](https://travis-ci.org/Drarok/supermodel)

```php
<?php

use Zerifas\Supermodel\AutoAccessorsTrait;
use Zerifas\Supermodel\Model;
use Zerifas\Supermodel\Relation\BelongsToRelation;

class PostModel extends Model
{
    use AutoAccessorsTrait;

    public static function getTableName(): string
    {
        return 'posts';
    }

    public static function getColumns(): array
    {
        return [
            'id',
            'userId',
            'title',
            'body',
        ];
    }

    public static function getValueTransformers(): array
    {
        return [];
    }

    public static function getRelations(): array
    {
        return [
            'user' => new BelongsToRelation(UserModel::class, 'id', 'userId'),
        ];
    }
}
```

```php
<?php

use Zerifas\Supermodel\AutoAccessorsTrait;
use Zerifas\Supermodel\Model;

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
            'name',
        ];
    }

    public static function getValueTransformers(): array
    {
        return [];
    }

    public static function getRelations(): array
    {
        return [];
    }
}
```

```php
<?php

use Zerifas\Supermodel\Cache\MemoryCache;
use Zerifas\Supermodel\Connection;

$dsn = 'mysql:host=localhost;dbname=test;charset=utf8;';
$conn = new Connection($dsn, 'root', 'password', new MemoryCache());

$post = $conn->find(PostModel::class)
    ->join('user')
    ->where([
        PostModel::greaterThan('id', 10),
        PostModel::equal('user.id', 22)
    ])
    ->getOne()
;

$post->getId();
$post->getTitle();
$post->getUser()->getId();
$post->getUser()->getName();
```
