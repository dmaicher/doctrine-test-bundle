<?php

namespace DAMA\DoctrineTestBundle\DependencyInjection;

use DAMA\DoctrineTestBundle\Doctrine\Cache\StaticArrayCache;
use DAMA\DoctrineTestBundle\Doctrine\DBAL\StaticConnectionFactory;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class DoctrineTestCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        /** @var DAMADoctrineTestExtension $extension */
        $extension = $container->getExtension('dama_doctrine_test');
        $config = $extension->getProcessedConfig();

        if ($config[Configuration::ENABLE_STATIC_CONNECTION]) {
            $factoryDef = new Definition(StaticConnectionFactory::class);
            $factoryDef
                ->setDecoratedService('doctrine.dbal.connection_factory')
                ->addArgument(new Reference('dama.doctrine.dbal.connection_factory.inner'))
            ;
            $container->setDefinition('dama.doctrine.dbal.connection_factory', $factoryDef);
        }

        $cacheNames = [];

        if ($config[Configuration::STATIC_META_CACHE]) {
            $cacheNames[] = 'doctrine.orm.%s_metadata_cache';
        }

        if ($config[Configuration::STATIC_QUERY_CACHE]) {
            $cacheNames[] = 'doctrine.orm.%s_query_cache';
        }

        $connectionNames = array_keys($container->getParameter('doctrine.connections'));

        foreach ($cacheNames as $cacheName) {
            foreach ($connectionNames as $name) {
                $cacheServiceId = sprintf($cacheName, $name);
                if ($container->hasAlias($cacheServiceId)) {
                    $container->removeAlias($cacheServiceId);
                }
                $cache = new Definition(StaticArrayCache::class);
                $cache->addMethodCall('setNamespace', [sha1($cacheServiceId)]); //make sure we have no key collisions
                $container->setDefinition($cacheServiceId, $cache);
            }
        }
    }
}
