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
    public function process(ContainerBuilder $container): void
    {
        /** @var DAMADoctrineTestExtension $extension */
        $extension = $container->getExtension('dama_doctrine_test');
        $config = $extension->getProcessedConfig();

        /** @var bool|array $enableStaticConnectionsConfig */
        $enableStaticConnectionsConfig = $config[Configuration::ENABLE_STATIC_CONNECTION];

        if ($enableStaticConnectionsConfig !== false) {
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
        if (is_array($enableStaticConnectionsConfig)) {
            $this->validateConnectionNames(array_keys($enableStaticConnectionsConfig), $connectionNames);
        }

        foreach ($connectionNames as $name) {
            if ($enableStaticConnectionsConfig === true
                || isset($enableStaticConnectionsConfig[$name]) && $enableStaticConnectionsConfig[$name] === true
            ) {
                $this->addConnectionOptions($container, $name);
            }

            foreach ($cacheNames as $cacheName) {
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

    private function validateConnectionNames(array $configNames, array $existingNames): void
    {
        $unknown = array_diff($configNames, $existingNames);

        if (count($unknown)) {
            throw new \InvalidArgumentException(sprintf('Unknown doctrine dbal connection name(s): %s.', implode(', ', $unknown)));
        }
    }

    private function addConnectionOptions(ContainerBuilder $container, string $name): void
    {
        $connectionDefinition = $container->getDefinition(sprintf('doctrine.dbal.%s_connection', $name));
        $connectionOptions = $connectionDefinition->getArgument(0);
        $connectionOptions['dama.keep_static'] = true;
        $connectionDefinition->replaceArgument(0, $connectionOptions);
    }
}
