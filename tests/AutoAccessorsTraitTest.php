<?php

namespace Zerifas\Supermodel\Test;

use PHPUnit\Framework\TestCase;
use Zerifas\Supermodel\Test\Model\PostModel;

class AutoAccessorsTraitTest extends TestCase
{
    private $model;

    protected function setUp()
    {
        parent::setUp();
        $this->model = new PostModel();
    }

    public function testGetter() {
        $this->assertNull($this->model->getAuthorId());
    }

    public function testSetter()
    {
        $this->model->setAuthorId(1);
        $this->assertEquals(1, $this->model->getAuthorId());
    }

    public function testInvalidGetter()
    {
        $this->expectExceptionMessage('Property nonExistent does not exist on ' . PostModel::class);
        $this->model->getNonExistent();
    }

    public function testInvalidSetter()
    {
        $this->expectExceptionMessage('Property nonExistent does not exist on ' . PostModel::class);
        $this->model->setNonExistent(1);
    }

    public function testInvalidMethodName()
    {
        $this->expectExceptionMessage('No such method: shouldThrow');
        $this->model->shouldThrow();
    }

    public function testInvalidArguments()
    {
        $this->expectExceptionMessage('Invalid auto-accessor call: getAuthorId(integer, ArrayObject)');
        $this->model->getAuthorId(1, new \ArrayObject([]));
    }
}
