<?php

namespace Raducorp\CassandraBundle\Service;

use \Cassandra\Exception\ConfigurationException;
use CassandraBundle\Repository\EntityRepository;

class CassandraService
{
    const ID = 'cassandra';
    
    protected $keyspaces    = [];
    protected $sessions     = [];
    protected $repositories = [];
    protected $cluster      = null;
    protected $async        = false;

    /**
     * @param int $port
     * @param bool|false $async
     * @param array $keyspaces
     */
    public function __construct($port = 9042, $async = false, array $keyspaces)
    {
        $builder = \Cassandra::cluster();                             // cluster(client) builder
        //$builder->withPort($port);
        $this->cluster = $builder->build();                           // connect to localhost by default
        //$session = $this->cluster->connect('promotech');            // create session, optionaly w/ scoped keyspace
        $this->async     = $async;
        $this->keyspaces = $keyspaces;
    }

    /**
     * @param string $keyspace
     * @return \Cassandra\Session
     */
    public function getSession($keyspace = 'default')
    {
        if (!isset($this->sessions[$keyspace])) {
            // create session
            if (isset($this->keyspaces[$keyspace])) {
                $session = $this->cluster->connect($this->keyspaces[$keyspace]);
                $this->sessions[$keyspace] = $session;
                return $session;
            }
        }

        throw new ConfigurationException("Session could not be created or keyspace({$keyspace}) not found!");
    }

    /**
     * @param  string $repository
     * @param  string $keyspace
     * @return EntityRepository
     */
    public function getRepository($repository, $keyspace = 'default')
    {
        if (!is_string($repository) || !is_string($keyspace)) {
            throw new ConfigurationException("That is not a string");
        } else if(!in_array($keyspace, $this->keyspaces)) {
            throw new ConfigurationException("Connection has not been configured!");
        }
        
        $message = "Could not find repo!";
        list($bundle, $entity) = explode(':', $repository);
        if (!isset($this->repositories[$keyspace][$entity])) {
            $repository = $bundle.'\\'.$entity . 'Repository';
            $repo = new $repository($this->getSession($keyspace));
            if (($repo instanceof EntityRepository) === true) {
                $this->repositories[$keyspace][$entity] = $repo;
                return $repo;
            }
            $message = "Not on instance of EntityRepository!";
        }

        throw new ConfigurationException($message);
    }
}
