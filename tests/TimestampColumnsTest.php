<?php

namespace Zerifas\Supermodel\Test;

class TimestampColumnsTest extends AbstractTestCase
{
    public function testCreateFromArray()
    {
        $model = FakeModel::createFromArray([
            'fake:id'        => 1,
            'fake:createdAt' => '2016-01-01 20:00:00',
            'fake:updatedAt' => '2016-01-01 20:00:00',
            'fake:enabled'   => 1,
        ]);

        $now = date('Y-m-d H:i:s');
        $expected = [
            'id'        => 1,
            'createdAt' => '2016-01-01 20:00:00',
            'updatedAt' => $now,
            'enabled'   => 1,
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
            'enabled'   => null,
        ];

        $this->assertEquals($expected, $model->toArray());
    }
}
