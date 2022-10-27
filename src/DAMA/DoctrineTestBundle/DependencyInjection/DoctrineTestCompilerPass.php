<?php

namespace DAMA\DoctrineTestBundle\DependencyInjection;

use DAMA\DoctrineTestBundle\Doctrine\Cache\Psr6StaticArrayCache;
use DAMA\DoctrineTestBundle\Doctrine\Cache\StaticArrayCache;
use DAMA\DoctrineTestBundle\Doctrine\DBAL\Middleware;
use Doctrine\Common\Cache\Cache;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class DoctrineTestCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $transactionalBehaviorEnabledConnections = $this->getTransactionEnabledConnectionNames($container);
        $container->register('dama.doctrine.dbal.middleware', Middleware::class);
        $cacheNames = [];

        if ($container->getParameter('dama.'.Configuration::STATIC_META_CACHE)) {
            $cacheNames[] = 'doctrine.orm.%s_metadata_cache';
        }

        if ($container->getParameter('dama.'.Configuration::STATIC_QUERY_CACHE)) {
            $cacheNames[] = 'doctrine.orm.%s_query_cache';
        }

        $connectionNames = array_keys($container->getParameter('doctrine.connections'));

        foreach ($connectionNames as $name) {
            if (in_array($name, $transactionalBehaviorEnabledConnections, true)) {
                $this->modifyConnectionService($container, $name);
            }

            foreach ($cacheNames as $cacheName) {
                $cacheServiceId = sprintf($cacheName, $name);

                if (!$container->has($cacheServiceId)) {
                    // might happen if ORM is not used
                    continue;
                }

                $definition = $container->findDefinition($cacheServiceId);
                while (!$definition->getClass() && $definition instanceof ChildDefinition) {
                    $definition = $container->findDefinition($definition->getParent());
                }

                $this->registerStaticCache($container, $definition, $cacheServiceId);
            }
        }

        $container->getParameterBag()->remove('dama.'.Configuration::ENABLE_STATIC_CONNECTION);
        $container->getParameterBag()->remove('dama.'.Configuration::STATIC_META_CACHE);
        $container->getParameterBag()->remove('dama.'.Configuration::STATIC_QUERY_CACHE);
    }

    private function modifyConnectionService(ContainerBuilder $container, string $name): void
    {
        $connectionDefinition = $container->getDefinition(sprintf('doctrine.dbal.%s_connection', $name));

        $connectionDefinition->replaceArgument(
            0,
            $this->getModifiedConnectionOptions($connectionDefinition->getArgument(0), $name),
        );

        $connectionConfig = $container->getDefinition(sprintf('doctrine.dbal.%s_connection.configuration', $name));
        $methodCalls = $connectionConfig->getMethodCalls();
        $middlewareRef = new Reference('dama.doctrine.dbal.middleware');
        $hasMiddlewaresMethodCall = false;
        foreach ($methodCalls as &$methodCall) {
            if ($methodCall[0] === 'setMiddlewares') {
                $hasMiddlewaresMethodCall = true;
                // our middleware needs to be the first one here so we wrap the "native" driver
                $methodCall[1][0] = array_merge([$middlewareRef], $methodCall[1][0]);
            }
        }

        if (!$hasMiddlewaresMethodCall) {
            $methodCalls[] = ['setMiddlewares', [[$middlewareRef]]];
        }

        $connectionConfig->setMethodCalls($methodCalls);
    }

    private function getModifiedConnectionOptions(array $options, string $name): array
    {
        $connectionOptions = array_merge($options, [
            'dama.keep_static' => true,
            'dama.connection_name' => $name,
        ]);

        if (isset($connectionOptions['primary'])) {
            $connectionOptions['primary'] = array_merge($connectionOptions['primary'], [
                'dama.keep_static' => true,
                'dama.connection_name' => $name,
            ]);
        }

        if (isset($connectionOptions['replica']) && is_array($connectionOptions['replica'])) {
            foreach ($connectionOptions['replica'] as $replicaName => &$replica) {
                $replica = array_merge($replica, [
                    'dama.keep_static' => true,
                    'dama.connection_name' => sprintf('%s.%s', $name, $replicaName),
                ]);
            }
        }

        return $connectionOptions;
    }

    private function registerStaticCache(
        ContainerBuilder $container,
        Definition $originalCacheServiceDefinition,
        string $cacheServiceId
    ): void {
        $cache = new Definition();
        $namespace = sha1($cacheServiceId);

        if (is_a($originalCacheServiceDefinition->getClass(), CacheItemPoolInterface::class, true)) {
            $cache->setClass(Psr6StaticArrayCache::class);
            $cache->setArgument(0, $namespace); // make sure we have no key collisions
        } elseif (is_a($originalCacheServiceDefinition->getClass(), Cache::class, true)) {
            $cache->setClass(StaticArrayCache::class);
            $cache->addMethodCall('setNamespace', [$namespace]); // make sure we have no key collisions
        } else {
            throw new \InvalidArgumentException(sprintf('Unsupported cache class "%s" found on service "%s".', $originalCacheServiceDefinition->getClass(), $cacheServiceId));
        }

        if ($container->hasAlias($cacheServiceId)) {
            $container->removeAlias($cacheServiceId);
        }
        $container->setDefinition($cacheServiceId, $cache);
    }

    /**
     * @return string[]
     */
    private function getTransactionEnabledConnectionNames(ContainerBuilder $container): array
    {
        /** @var bool|array $enableStaticConnectionsConfig */
        $enableStaticConnectionsConfig = $container->getParameter('dama.'.Configuration::ENABLE_STATIC_CONNECTION);

        $connectionNames = array_keys($container->getParameter('doctrine.connections'));
        if (is_array($enableStaticConnectionsConfig)) {
            $this->validateConnectionNames(array_keys($enableStaticConnectionsConfig), $connectionNames);
        }

        $enabledConnections = [];

        foreach ($connectionNames as $name) {
            if ($enableStaticConnectionsConfig === true
                || isset($enableStaticConnectionsConfig[$name]) && $enableStaticConnectionsConfig[$name] === true
            ) {
                $enabledConnections[] = $name;
            }
        }

        return $enabledConnections;
    }

    /**
     * @param string[] $configNames
     * @param string[] $existingNames
     */
    private function validateConnectionNames(array $configNames, array $existingNames): void
    {
        $unknown = array_diff($configNames, $existingNames);

        if (count($unknown)) {
            throw new \InvalidArgumentException(sprintf('Unknown doctrine dbal connection name(s): %s.', implode(', ', $unknown)));
        }
    }
}
