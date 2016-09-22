<?php

namespace Zerifas\Supermodel\Test;

use Zerifas\Supermodel\AbstractModel;
use Zerifas\Supermodel\TimestampColumns;
use Zerifas\Supermodel\Transformer\Boolean as BooleanTransformer;
use Zerifas\Supermodel\Transformer\DateTime as DateTimeTransformer;

class FakeModel extends AbstractModel
{
    use TimestampColumns;

    protected static $columns = [
        'id',
        'createdAt',
        'updatedAt',
        'enabled',
    ];

    protected static $columnMap = [
        'enabled' => 'isActive',
    ];

    protected static $valueTransformers = [
        'createdAt' => DateTimeTransformer::class,
        'updatedAt' => DateTimeTransformer::class,
        'enabled'   => BooleanTransformer::class,
    ];

    protected $isActive;

    public static function getTablename()
    {
        return 'fake';
    }

    public function setIsActive($value)
    {
        $this->isActive = $value;
        return $this;
    }

    public function getIsActive()
    {
        return $this->isActive;
    }

    public function isActive()
    {
        return $this->isActive;
    }

    public function changeId()
    {
        $this->setId(1234);
    }
}
