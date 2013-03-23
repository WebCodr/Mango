<?php

namespace Mango\Tests;

use Mango\Mango;
use Mango\DocumentManager;
use Mango\Tests\Document\User;

class DocumentTest extends \PHPUnit_Framework_TestCase
{
    private function getConnection()
    {
        $mango = new Mango('mongodb://localhost:27017/mango-unittests');
        $dm = new DocumentManager($mango);

        return $mango;
    }

    public function tearDown()
    {
        User::where()->each(function($document) {
            $document->remove();
        });
    }

    public function testFind()
    {
        $this->getConnection();
        $ids = [];

        for ($i = 0; $i <= 3; $i++) {
            $user = new User();
            $user->name = "Test {$i}";
            $user->store();
            $ids[] = (string)$user->_id;
        }

        $result = call_user_func_array(['\Mango\Tests\Document\User', 'find'], $ids);

        self::assertEquals(4, $result->count());
    }

    public function testStore()
    {
        $mango = $this->getConnection();
        $document = new User();
        $document->name = 'Foo Bar';
        $document->store();
        $query = ['name' => 'Foo Bar'];
        self::assertEquals(1, $document::where($query)->count());

        $document->remove();
        self::assertEquals(0, $document::where($query)->count());
    }

    public function testQuery()
    {
        $mango = $this->getConnection();
        $document = new User();
        $document->name = 'Foo Bar';
        $document->store();
        $query = ['name' => 'Foo Bar'];

        $user = $document::where($query)->head();
        self::assertEquals($document->getDehydratedProperties(), $user->getDehydratedProperties());

        $document->remove();
        self::assertEquals(0, $document::where($query)->count());
    }

    public function testHydration()
    {
        $mango = $this->getConnection();
        $document = new User();
        $document->name = 'Foo Bar';
        $document->store();
        $query = ['name' => 'Foo Bar'];

        $user = $document::where($query)->head();
        $user->updated_at = new \DateTime('+4 hours');
        $user->name = 'Foo Bar 2';
        $user->store();
        self::assertEquals(0, $document::where($query)->count());
        self::assertEquals(1, $document::where(['name' => 'Foo Bar 2'])->count());

        $document->remove();
        self::assertEquals(0, $document::find($user->getId())->count());
    }
}