<?php
namespace tests\codeception\unit;

use yii\codeception\TestCase;
use jones\wschat\components\AbstractStorage;
use jones\wschat\components\DbStorage;

class AbstractStorageTest extends TestCase
{
    /**
     * @covers \jones\wschat\components\AbstractStorage::factory
     */
    public function testPgsqlStorage()
    {
        $storage = AbstractStorage::factory('pgsql');
        $this->assertInstanceOf(DbStorage::class, $storage);
    }

    /**
     * @covers \jones\wschat\components\AbstractStorage::factory
     */
    public function testMysqlStorage()
    {
        $storage = AbstractStorage::factory('mysql');
        $this->assertInstanceOf(DbStorage::class, $storage);
    }
}