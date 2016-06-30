<?php

namespace CassandraBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $root = $treeBuilder->root('cassandra');
        $root->children()
            ->scalarNode('port')->defaultValue(9042)->end()
            ->scalarNode('async')->defaultValue(false)->end()
            ->arrayNode('keyspaces')->prototype('scalar')->end();
        return $treeBuilder;

    }
}
