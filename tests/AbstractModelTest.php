<?php

namespace Zerifas\Supermodel\Test;

use Zerifas\Supermodel\AbstractModel;

class AbstractModelTest extends AbstractTestCase
{
    public function testGetPDOOptions()
    {
        $expected = [
            \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_STRINGIFY_FETCHES  => false,
            \PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        $this->assertEquals($expected, AbstractModel::getPDOOptions());
    }

    public function testGetColumns()
    {
        $expected = [
            '`fake`.`id` AS `fake:id`',
            '`fake`.`createdAt` AS `fake:createdAt`',
            '`fake`.`updatedAt` AS `fake:updatedAt`',
            '`fake`.`enabled` AS `fake:enabled`',
        ];

        $this->assertEquals($expected, FakeModel::getColumns());
    }

    public function testGetColumn()
    {
        $this->assertEquals('`fake`.`id`', FakeModel::getColumn('id'));
    }

    public function testTransformedColumn()
    {
        $model = FakeModel::createFromArray([
            'fake:id' => 1,
            'fake:createdAt' => '2016-01-01 00:00:00',
            'fake:updatedAt' => '2016-01-01 00:00:00',
            'fake:enabled'   => 1,
        ]);
        $this->assertSame(true, $model->isActive());
    }

    public function testFindAll()
    {
        // Must use iterator_to_array to run the Generator.
        iterator_to_array(FakeModel::findAll($this->db));

        $expected = implode(' ', [
            'SELECT',
            '`fake`.`id` AS `fake:id`, `fake`.`createdAt` AS `fake:createdAt`,',
            '`fake`.`updatedAt` AS `fake:updatedAt`, `fake`.`enabled` AS `fake:enabled`',
            'FROM `fake`',
        ]);

        $stmts = $this->db->getStatements();
        $actual = $stmts[0];
        $this->assertEquals($expected, $actual);
    }

    public function testFindById()
    {
        $model = FakeModel::findbyId($this->db, 1);

        $expected = implode(' ', [
            'SELECT',
            '`fake`.`id` AS `fake:id`, `fake`.`createdAt` AS `fake:createdAt`,',
            '`fake`.`updatedAt` AS `fake:updatedAt`, `fake`.`enabled` AS `fake:enabled`',
            'FROM `fake`',
            'WHERE `fake`.`id` = ?',
            'LIMIT 1',
        ]);

        $stmts = $this->db->getStatements();
        $actual = $stmts[0];
        $this->assertEquals($expected, $actual);

        $this->assertSame(1, $model->getId());
        $this->assertSame(true, $model->isActive());
    }

    public function testFindByIdNoMatch()
    {
        PDOStatement::setDefaultIsExhausted(true);

        $model = FakeModel::findbyId($this->db, 1);

        $expected = implode(' ', [
            'SELECT',
            '`fake`.`id` AS `fake:id`, `fake`.`createdAt` AS `fake:createdAt`,',
            '`fake`.`updatedAt` AS `fake:updatedAt`, `fake`.`enabled` AS `fake:enabled`',
            'FROM `fake`',
            'WHERE `fake`.`id` = ?',
            'LIMIT 1',
        ]);

        $stmts = $this->db->getStatements();
        $actual = $stmts[0];
        $this->assertEquals($expected, $actual);

        $this->assertSame(false, $model);
    }

    public function testGetTableNameException()
    {
        $this->setExpectedException('Exception', 'getTableName not overridden in Zerifas\Supermodel\AbstractModel');
        AbstractModel::getTableName();
    }

    public function testDelete()
    {
        $model = FakeModel::createFromArray([
            'fake:id' => 1,
            'fake:createdAt' => '2016-01-01 00:00:00',
            'fake:updatedAt' => '2016-01-01 00:00:00',
            'fake:enabled'   => 1,
        ], $this->db);

        $this->assertSame(false, $model->isDeleted());
        $model->delete();
        $model->delete();
        $this->assertSame(true, $model->isDeleted());

        $expected = 'DELETE FROM `fake` WHERE `fake`.`id` = :id';

        $stmts = $this->db->getStatements();
        $actual = end($stmts);
        $this->assertEquals($expected, $actual);
    }

    public function testDeleteWithoutDb()
    {
        $this->setExpectedException('Exception', 'Cannot delete without a database connection.');

        $model = new FakeModel();
        $model->delete();
    }

    public function testDeleteWithoutId()
    {
        $this->setExpectedException('Exception', 'Cannot delete a nonexistent Zerifas\Supermodel\Test\FakeModel');

        $model = new FakeModel($this->db);
        $model->delete();
    }

    public function testSaveWithCreate()
    {
        $model = new FakeModel($this->db);
        $model->save();

        $expected = 'INSERT INTO `fake` SET `createdAt` = :createdAt, `updatedAt` = :updatedAt, `enabled` = :enabled';
        $stmts = $this->db->getStatements();
        $actual = preg_replace('/\s+/', ' ', end($stmts));

        $this->assertEquals($expected, $actual);
        $this->assertEquals(1, $model->getId());
    }

    public function testSaveWithoutDb()
    {
        $this->setExpectedException('Exception', 'Cannot save without a database connection.');

        $model = new FakeModel();
        $model->save();
    }

    public function testSaveDeleted()
    {
        $this->setExpectedException('Exception', 'Cannot save a deleted Zerifas\Supermodel\Test\FakeModel');

        $model = FakeModel::createFromArray([
            'fake:id' => 1,
            'fake:createdAt' => '2016-01-01 00:00:00',
            'fake:updatedAt' => '2016-01-01 00:00:00',
            'fake:enabled'   => 1,
        ], $this->db);

        $model->delete();
        $model->save();
    }

    public function testSaveUpdate()
    {
        $model = FakeModel::createFromArray([
            'fake:id' => 1,
            'fake:createdAt' => '2016-01-01 00:00:00',
            'fake:updatedAt' => '2016-01-01 00:00:00',
            'fake:enabled'   => 1,
        ], $this->db);

        $model->save();

        $expected = 'UPDATE `fake` SET `createdAt` = :createdAt, `updatedAt` = :updatedAt, `enabled` = :enabled WHERE id = :id LIMIT 1';
        $stmts = $this->db->getStatements();
        $actual = preg_replace('/\s+/', ' ', end($stmts));

        $this->assertEquals($expected, $actual);
    }

    public function testSetId()
    {
        $this->setExpectedException('Exception', 'Cannot change the primary key of Zerifas\Supermodel\Test\FakeModel');
        $model = new FakeModel();
        $model->changeId();
        $model->changeId();
    }
}
