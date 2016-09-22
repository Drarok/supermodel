<?php

namespace Zerifas\Supermodel\Test;

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
            'FROM posts',
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
            'FROM posts',
            'INNER JOIN users ON users.id = posts.userId',
            'WHERE posts.createdAt = ?',
            'LIMIT 10',
        ]);

        $statements = $this->db->getStatements();
        $this->assertEquals($expected, $statements[0]);
    }
}
