# Supermodel

Supermodel is a super-simple model library for PHP >= 5.5.

## Installation

    composer require zerifas/supermodel

## Usage

`src/model/UserModel.php`:
```php
<?php

namespace YourApp\Model;

use Zerifas\Supermodel\AbstractModel;
use Zerifas\Supermodel\TimestampColumns;

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
        'isActive',
    ];

    // You can map columns to different property names.
    protected static $columnMap = [
        'isActive' => 'enabled',
    ];

    // Transformers are keyed on column name, and are used to automatically convert database
    // values into scalars or objects in PHP, and vice versa.
    protected static $valueTransformers = [
        'createdAt' => 'Zerifas\\Supermodel\\Transformer\\DateTime',
        'updatedAt' => 'Zerifas\\Supermodel\\Transformer\\DateTime',
    ];

    protected $username;
    protected $enabled;

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

    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;
        return $this;
    }

    public function getEnabled()
    {
        return $this->enabled;
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
        'createdAt' => 'Zerifas\\Supermodel\\Transformer\\DateTime',
        'updatedAt' => 'Zerifas\\Supermodel\\Transformer\\DateTime',
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
        $stmt = (new QueryBuilder($db))
            ->select(static::getColumns())
            ->addColumns(UserModel::getColumns())
            ->from(static::getTableName())
            ->join(UserModel::getTableName(), UserModel::getColumn('id'), static::getColumn('userId'))
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
