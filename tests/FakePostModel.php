<?php

namespace Zerifas\Supermodel\Test;

use Zerifas\Supermodel\AbstractModel;
use Zerifas\Supermodel\TimestampColumns;
use Zerifas\Supermodel\Transformer\DateTime as DateTimeTransformer;

class FakePostModel extends AbstractModel
{
    use TimestampColumns;

    protected static $columns = [
        'id',
        'createdAt',
        'updatedAt',
        'fakeId',
        'title',
    ];

    protected static $valueTransformers = [
        'createdAt' => DateTimeTransformer::class,
        'updatedAt' => DateTimeTransformer::class,
    ];

    protected $fakeId;
    protected $title;

    public static function getTablename()
    {
        return 'fakePosts';
    }

    protected function setFakeId($fakeId)
    {
        $this->fakeId = $fakeId;
        return $this;
    }

    public function getFakeId()
    {
        return $this->fakeId;
    }

    protected function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    public function getTitle()
    {
        return $this->title;
    }
}
