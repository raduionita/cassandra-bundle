<?php
namespace CassandraBundle\Repository;

use Cassandra\Session;

class EntityRepository
{
    protected $session = null;
    
    public function __construct(Session $session)
    {
        $this->session = $session;
    }
}