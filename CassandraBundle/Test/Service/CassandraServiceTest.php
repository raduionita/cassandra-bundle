<?php

namespace CassandraBundle\Tests\Service;

use CassandraBundle\Service\CassandraService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CassandraServiceTest extends KernelTestCase
{
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
    }
    
    public function testInit()
    {
        static::bootKernel();
        /** @var CassandraService $cassandra */
        $cassandra = static::$kernel->getContainer()->get(CassandraService::ID);
        $this->assertInstanceOf('CassandraBundle\Service\CassandraService', $cassandra, "Cassandra service init test");
    }
    
    public function testDefaultSession()
    {
        static::bootKernel();
        /** @var CassandraService $cassandra */
        $cassandra = static::$kernel->getContainer()->get(CassandraService::ID);
        $session = $cassandra->getSession('default');
        $this->assertInstanceOf('\Cassandra\Session', $session, "Cassandra default session test");
    }
    
    public function testSelect()
    {
        static::bootKernel();
        /** @var CassandraService $cassandra */
        $cassandra = static::$kernel->getContainer()->get(CassandraService::ID);
        $session = $cassandra->getSession('default');
        
        $statement = $session->prepare("SELECT * FROM test");
        $result = $session->execute($statement);
        foreach ($result as $row) {
            $this->assertNotEmpty($row, "Cassandra select test");
        }
    }
    
    public function testInsert()
    {
        static::bootKernel();
        /** @var CassandraService $cassandra */
        $cassandra = static::$kernel->getContainer()->get(CassandraService::ID);
        $session = $cassandra->getSession('default');
        
        $statement = $session->prepare("SELECT * FROM test");
        $result = $session->execute($statement);
        $count = $result->count();
        $lastId = 0;
        foreach ($result as $row) {
            $lastId = max($row['id'], $lastId);
        }
        
        $user = uniqid();
        $statement = $session->prepare("INSERT INTO test (id, active, created, email, modified, username) VALUES (". ($lastId+1) .", true, dateof(now()), '". $user ."@emag.ro', dateof(now()), '". $user ."')");
        $session->execute($statement);
        
        $statement = $session->prepare("SELECT * FROM test");
        $result = $session->execute($statement);
        
        $this->assertEquals($result->count(), $count + 1, "Cassandra insert test");
    }
}