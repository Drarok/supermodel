# Supermodel [![Build Status](https://github.com/Drarok/supermodel/actions/workflows/php.yml/badge.svg)](https://github.com/Drarok/supermodel/actions/workflows/php.yml) [![Coverage Status](https://coveralls.io/repos/github/Drarok/supermodel/badge.svg?branch=master)](https://coveralls.io/github/Drarok/supermodel?branch=master)

Supermodel is a super-simple model library for PHP >= 8.0.

## Installation

```
composer require 'zerifas/supermodel'
```

## Usage

`…/PostModel.php`:
```php
<?php

use Zerifas\Supermodel\AutoAccessorsTrait;
use Zerifas\Supermodel\TimestampedModel;
use Zerifas\Supermodel\Relation\BelongsToRelation;
use Zerifas\Supermodel\Transformer\BooleanTransformer;

class PostModel extends TimestampedModel
{
    use AutoAccessorsTrait;

    protected $userId;
    protected $title;
    protected $body;

    public static function getTableName(): string
    {
        return 'posts';
    }

    public static function getColumns(): array
    {
        return [
            'id',
            'createdAt', // Handled automatically in TimestampedModel
            'updatedAt', // Handled automatically in TimestampedModel
            'userId',
            'title',
            'body',
            'enabled',
        ];
    }

    public static function getValueTransformers(): array
    {
        return array_merge(parent::getValueTransformers(), [
            'enabled' => BooleanTransformer::class,
        ]);
    }

    public static function getRelations(): array
    {
        return [
            'user' => new BelongsToRelation(UserModel::class, 'userId'),
        ];
    }
}
```

`…/UserModel.php`:
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
            'username',
        ];
    }

    public static function getValueTransformers(): array
    {
        return [];
    }

    public static function getRelations(): array
    {
        return [
            'posts' => new HasManyRelation(PostModel::class, 'userId'),
        ];
    }
}
```

`examples.php`:
```php
<?php

use Zerifas\Supermodel\Cache\MemoryCache;
use Zerifas\Supermodel\Connection;

$dsn = 'mysql:host=localhost;dbname=test;charset=utf8;';
$conn = new Connection($dsn, 'root', 'password', new MemoryCache());

$post = $conn->find(PostModel::class, 'p')
    ->join('user', 'u')
    ->where('p.id > ?', 10)
    ->where('u.id = ?', 22)
    ->orderBy('u.username')
    ->orderBy('p.createdAt', 'DESC')
    ->fetchOne()
;

$post->getId();
$post->getTitle();
$post->getUser()->getId();
$post->getUser()->getName();

// The `fetchAll` method returns a Generator, not an array
$users = $conn->find(UserModel::class, 'u')
    ->join('posts', 'p')
    ->where('p.userId = ?', 2)
    ->where('p.title LIKE ?', 'News%')
    ->fetchAll()
;

foreach ($users as $user) {
    foreach ($user->getPosts() as $post) {
        // $post->getId();
    }
}
```
