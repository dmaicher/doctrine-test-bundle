<?php

namespace DAMA\DoctrineTestBundle\DependencyInjection;

use DAMA\DoctrineTestBundle\Doctrine\Cache\Psr6StaticArrayCache;
use DAMA\DoctrineTestBundle\Doctrine\Cache\StaticArrayCache;
use DAMA\DoctrineTestBundle\Doctrine\DBAL\Middleware;
use DAMA\DoctrineTestBundle\Doctrine\DBAL\StaticConnectionFactory;
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
        /** @var string[] $transactionalBehaviorEnabledConnections */
        $transactionalBehaviorEnabledConnections = $container->getParameter('dama.doctrine.enabled_connections');

        if (count($transactionalBehaviorEnabledConnections) > 0) {
            $factoryDef = new Definition(StaticConnectionFactory::class);
            $factoryDef
                ->setDecoratedService('doctrine.dbal.connection_factory')
                ->addArgument(new Reference('dama.doctrine.dbal.connection_factory.inner'))
            ;
            $container->setDefinition('dama.doctrine.dbal.connection_factory', $factoryDef);
        }

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
                $this->addConnectionOptions($container, $name);
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

        $container->getParameterBag()->remove('dama.'.Configuration::STATIC_META_CACHE);
        $container->getParameterBag()->remove('dama.'.Configuration::STATIC_QUERY_CACHE);
        $container->getParameterBag()->remove('dama.doctrine.enabled_connections');
    }

    private function addConnectionOptions(ContainerBuilder $container, string $name): void
    {
        $connectionDefinition = $container->getDefinition(sprintf('doctrine.dbal.%s_connection', $name));
        $connectionOptions = $connectionDefinition->getArgument(0);
        $connectionOptions['dama.keep_static'] = true;
        $connectionOptions['dama.connection_name'] = $name;
        $connectionDefinition->replaceArgument(0, $connectionOptions);

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
}
