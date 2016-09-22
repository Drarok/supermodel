<?php

namespace Zerifas\Supermodel\Test;

use Zerifas\Supermodel\QueryBuilder;

class QueryBuilderTest extends AbstractTestCase
{
    public function testSimple()
    {
        $stmt = $this->qb
            ->select([
                'posts.id',
                'posts.createdAt',
                'posts.updatedAt',
                'posts.title',
                'posts.body',
            ])
            ->from('posts')
            ->execute()
        ;

        $expected = implode(' ', [
            'SELECT',
            'posts.id, posts.createdAt, posts.updatedAt, posts.title, posts.body',
            'FROM `posts`',
        ]);

        $statements = $this->db->getStatements();
        $this->assertEquals($expected, $statements[0]);
    }

    public function testAllBuilderMethods()
    {
        $stmt = $this->qb
            ->select([
                'posts.id',
                'posts.createdAt',
                'posts.updatedAt',
                'posts.title',
                'posts.body',
            ])
            ->addColumns([
                'user.id',
                'user.username',
                'user.password',
            ])
            ->from('posts')
            ->join('users', 'users.id', 'posts.userId')
            ->where(['posts.createdAt' => 1])
            ->limit(10)
            ->execute()
        ;

        $expected = implode(' ', [
            'SELECT',
            'posts.id, posts.createdAt, posts.updatedAt, posts.title, posts.body,',
            'user.id, user.username, user.password',
            'FROM `posts`',
            'INNER JOIN users ON users.id = posts.userId',
            'WHERE posts.createdAt = ?',
            'LIMIT 10',
        ]);

        $statements = $this->db->getStatements();
        $this->assertEquals($expected, $statements[0]);
    }

    public function testWithClass()
    {
        $qb = new QueryBuilder($this->db, FakeModel::class);
        $qb->where([
            'id' => 1,
        ]);
        $qb->execute();

        $expected = implode(' ', [
            'SELECT `fake`.`id` AS `fake:id`, `fake`.`createdAt` AS `fake:createdAt`,',
            '`fake`.`updatedAt` AS `fake:updatedAt`, `fake`.`enabled` AS `fake:enabled`',
            'FROM `fake` WHERE `fake`.`id` = ?',
        ]);

        $stmts = $this->db->getStatements();
        $actual = end($stmts);
        $this->assertEquals($expected, $actual);
    }

    public function testWithInvalidClass()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Zerifas\Supermodel\QueryBuilder only accepts a class name of a ' .
            'subclass of Zerifas\Supermodel\AbstractModel as its second parameter'
        );
        $qb = new QueryBuilder($this->db, static::class);
    }
}
