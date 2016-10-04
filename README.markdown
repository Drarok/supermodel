# Supermodel [![Build Status](https://travis-ci.org/Drarok/supermodel.svg?branch=master)](https://travis-ci.org/Drarok/supermodel)

Supermodel is a super-simple model library for PHP >= 5.5.

## Installation

    composer require zerifas/supermodel

## Usage

### Model Generation

The easiest way to start creating your models is to use the built-in model generation tool:

All `date`, `time`, and `datetime` columns are automatically transformed, to `DateTime` objects and `bit(1)` columns are transformed to booleans.

```bash
$ edit supermodel.json # see example supermodel.sample.json
$ vendor/bin/supermodel generate UserModel users > src/YourApp/Model/UserModel.php
```

If you have both a `createdAt` and `updatedAt` which are `datetime`, you can use the `--timestamp` option to automatically enable the use of the `TimestampColumns` trait, which will update these columns at the correct time:

```bash
$ vendor/bin/supermodel generate UserModel users --timestamps > src/YourApp/Model/UserModel.php
```

Finally, you can optionally transform `tinyint unsigned not null` columns to booleans:

```bash
$ vendor/bin/supermodel generate UserModel users --tinyint-bool > src/YourApp/Model/UserModel.php
```

### Manual Process

Here are some examples, demonstrating various ways you can use Supermodel.

`src/model/UserModel.php`:
```php
<?php

namespace YourApp\Model;

use Zerifas\Supermodel\AbstractModel;
use Zerifas\Supermodel\TimestampColumns;
use Zerifas\Supermodel\Transformer\Boolean as BooleanTransformer;
use Zerifas\Supermodel\Transformer\DateTime as DateTimeTransformer;

class UserModel extends AbstractModel
{
    // Automatically adds properties, getters, and setters for createdAt and updatedAt,
    // and automatically sets their values in the `toArray()` method.
    use TimestampColumns;

    // Define all the columns that are in the table.
    protected static $columns = [
        'id',
        'createdAt',
        'updatedAt',
        'username',
        'enabled',
    ];

    // You can map columns to different property names.
    protected static $columnMap = [
        'enabled' => 'active',
    ];

    // Transformers are keyed on column name, and are used to automatically convert database
    // values into scalars or objects in PHP, and vice versa.
    protected static $valueTransformers = [
        'createdAt' => DateTimeTransformer::class,
        'updatedAt' => DateTimeTransformer::class,
        'enabled'   => BooleanTransformer::class,
    ];

    protected $username;
    protected $active;

    public static function getTableName()
    {
        return 'users';
    }

    public function setUsername($value)
    {
        $this->username = $value;
        return $this;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function setActive($active)
    {
        $this->active = $active;
        return $this;
    }

    public function getActive()
    {
        return $this->active;
    }

    public function isActive()
    {
        return $this->active;
    }
}
```

`src/model/PostModel.php`:
```php
<?php

namespace YourApp\Model;

use Zerifas\Supermodel\AbstractModel;
use Zerifas\Supermodel\QueryBuider;
use Zerifas\Supermodel\TimestampColumns;
use Zerifas\Supermodel\Transformer\DateTime as DateTimeTransformer;

class PostModel extends AbstractModel
{
    use TimestampColumns;

    protected static $columns = [
        'id',
        'createdAt',
        'updatedAt',
        'userId',
        'title',
        'body',
    ];

    protected $userId;
    protected $title;
    protected $body;
    protected $user;

    protected static $valueTransformers = [
        'createdAt' => DateTimeTransformer::class,
        'updatedAt' => DateTimeTransformer::class,
    ];

    public static function getTableName()
    {
        return 'posts';
    }

    /**
     * Overridden findBy to automatically include the n:1 UserModel via SQL JOIN.
     *
     * @return Generator
     */
    public static function findBy(PDO $db, array $where = [])
    {
        $stmt = (new QueryBuilder($db, static::class))
            ->joinModel(UserModel::class, 'id', 'userId')
            ->where($where)
            ->execute()
        ;

        while (($row = $stmt->fetch())) {
            yield static::createFromArray($row, $db)
                ->setUser(UserModel::createFromArray($row, $db))
            ;
        }
    }

    public function setUser(UserModel $user)
    {
        $this->user = $user;
        $this->userId = $user->getId();
        return $this;
    }

    public function getUser()
    {
        return $this->user;
    }

    /**
     * Snip
     */
}
```

`examples.php`:
```php
<?php

use YourApp\Model\UserModel;
use YourApp\Model\PostModel;

$db = new PDO(
    'mysql:host=localhost;dbname=test;charset=utf8',
    'root',
    'P@55w0rd',
    AbstractModel::getPDOOptions()
);

// Load all users from the database
foreach (UserModel::findAll($db) as $user) {
    echo $user->getId(), ': ', $user->getUsername(), PHP_EOL;
}

// Load a single user from the database
$user = UserModel::findById($db, 1);
$data = $user->toArray();

// Create user
$user = new UserModel($db);
$user->setUsername('alice');
$user->save();

// Load all the posts (with their user)
foreach (PostModel::findAll($db) as $post) {
    echo $post->getId(), ': ', $post->getUser()->getUsername(), PHP_EOL;
}
```
