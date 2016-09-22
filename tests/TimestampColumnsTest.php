<?php

namespace Zerifas\Supermodel\Test;

use Zerifas\Supermodel\AbstractModel;
use Zerifas\Supermodel\TimestampColumns;
use Zerifas\Supermodel\Transformer\DateTime as DateTimeTransformer;


class FakeModel extends AbstractModel
{
    use TimestampColumns;

    protected static $columns = [
        'id',
        'createdAt',
        'updatedAt',
    ];

    protected static $valueTransformers = [
        'createdAt' => DateTimeTransformer::class,
        'updatedAt' => DateTimeTransformer::class,
    ];

    public static function getTablename()
    {
        return 'fake';
    }
}

class TimestampColumnsTest extends AbstractTestCase
{
    public function testCreateFromArray()
    {
        $model = FakeModel::createFromArray([
            'fake:id'        => 1,
            'fake:createdAt' => '2016-01-01 20:00:00',
            'fake:updatedAt' => '2016-01-01 20:00:00',
        ]);

        $now = date('Y-m-d H:i:s');
        $expected = [
            'id'        => 1,
            'createdAt' => '2016-01-01 20:00:00',
            'updatedAt' => $now,
        ];

        $this->assertEquals($expected, $model->toArray());
    }

    public function testNewInstance()
    {
        $model = new FakeModel();

        $now = date('Y-m-d H:i:s');
        $expected = [
            'createdAt' => $now,
            'updatedAt' => $now,
        ];

        $this->assertEquals($expected, $model->toArray());
    }
}
