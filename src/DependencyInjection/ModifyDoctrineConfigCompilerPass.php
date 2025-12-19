<?php

namespace DAMA\DoctrineTestBundle\DependencyInjection;

use DAMA\DoctrineTestBundle\Doctrine\Cache\Psr6StaticArrayCache;
use Doctrine\DBAL\Connection;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

final class ModifyDoctrineConfigCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $cacheNames = [];

        if ($container->getParameter('dama.'.Configuration::STATIC_META_CACHE)) {
            $cacheNames[] = 'doctrine.orm.%s_metadata_cache';
        }

        if ($container->getParameter('dama.'.Configuration::STATIC_QUERY_CACHE)) {
            $cacheNames[] = 'doctrine.orm.%s_query_cache';
        }

        /** @var array<string, mixed> $connections */
        $connections = $container->getParameter('doctrine.connections');
        $connectionNames = array_keys($connections);

        /** @var string[] $transactionalBehaviorEnabledConnections */
        $transactionalBehaviorEnabledConnections = $container->getParameter(
            AddMiddlewaresCompilerPass::TRANSACTIONAL_BEHAVIOR_ENABLED_CONNECTIONS,
        );
        $connectionKeys = $this->getConnectionKeys($container, $connectionNames);

        foreach ($connectionNames as $name) {
            if (in_array($name, $transactionalBehaviorEnabledConnections, true)) {
                $this->modifyConnectionService($container, $connectionKeys[$name] ?? null, $name);
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
        $container->getParameterBag()->remove('dama.'.Configuration::CONNECTION_KEYS);
        $container->getParameterBag()->remove(AddMiddlewaresCompilerPass::TRANSACTIONAL_BEHAVIOR_ENABLED_CONNECTIONS);
    }

    /**
     * @param string|array{primary: string, replicas: array<string, string>}|null $connectionKey
     */
    private function modifyConnectionService(ContainerBuilder $container, $connectionKey, string $name): void
    {
        $connectionDefinition = $container->getDefinition(sprintf('doctrine.dbal.%s_connection', $name));

        if (!$this->hasSavepointsEnabled($connectionDefinition)) {
            throw new \LogicException(sprintf('This bundle relies on savepoints for nested database transactions. You need to enable "use_savepoints" on the Doctrine DBAL config for connection "%s".', $name));
        }

        /** @var array<string, mixed> $connectionOptions */
        $connectionOptions = $connectionDefinition->getArgument(0);
        $connectionDefinition->replaceArgument(
            0,
            $this->getModifiedConnectionOptions($connectionOptions, $connectionKey, $name),
        );
    }

    /**
     * @param array<string, mixed>                                                $connectionOptions
     * @param array{primary: string, replicas: array<string, string>}|string|null $connectionKey
     *
     * @return array<string, mixed>
     */
    private function getModifiedConnectionOptions(
        array $connectionOptions,
        array|string|null $connectionKey,
        string $name
    ): array {
        if (!isset($connectionOptions['primary'])) {
            if (is_array($connectionKey)) {
                throw new \InvalidArgumentException(sprintf('Connection key for connection "%s" must be a string', $name));
            }

            $connectionOptions['dama.connection_key'] = $connectionKey ?? $name;

            return $connectionOptions;
        }

        $connectionOptions['dama.connection_key'] = $connectionKey['primary'] ?? $connectionKey ?? $name;
        /** @phpstan-ignore offsetAccess.nonOffsetAccessible */
        $connectionOptions['primary']['dama.connection_key'] = $connectionOptions['dama.connection_key'];

        if (!is_array($connectionOptions['replica'] ?? null)) {
            return $connectionOptions;
        }

        $replicaKeys = [];
        if (isset($connectionKey['replicas'])) {
            /** @var array<string> $definedReplicaNames */
            $definedReplicaNames = array_keys($connectionOptions['replica']);
            $this->validateConnectionNames(array_keys($connectionKey['replicas']), $definedReplicaNames);
            $replicaKeys = $connectionKey['replicas'];
        }

        foreach ($connectionOptions['replica'] as $replicaName => &$replicaOptions) {
            /** @phpstan-ignore offsetAccess.nonOffsetAccessible */
            $replicaOptions['dama.connection_key'] = $replicaKeys[$replicaName] ?? $connectionOptions['dama.connection_key'];
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
        $originalServiceClass = $originalCacheServiceDefinition->getClass();

        if ($originalServiceClass !== null && is_a($originalServiceClass, CacheItemPoolInterface::class, true)) {
            $cache->setClass(Psr6StaticArrayCache::class);
            $cache->setArgument(0, $namespace); // make sure we have no key collisions
        } else {
            throw new \InvalidArgumentException(sprintf('Unsupported cache class "%s" found on service "%s". Only PSR-6 caches are supported.', $originalCacheServiceDefinition->getClass(), $cacheServiceId));
        }

        if ($container->hasAlias($cacheServiceId)) {
            $container->removeAlias($cacheServiceId);
        }
        $container->setDefinition($cacheServiceId, $cache);
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

    private function hasSavepointsEnabled(Definition $connectionDefinition): bool
    {
        // DBAL 4 implicitly always enables savepoints
        if (!method_exists(Connection::class, 'getEventManager')) {
            return true;
        }

        foreach ($connectionDefinition->getMethodCalls() as $call) {
            if ($call[0] === 'setNestTransactionsWithSavepoints' && isset($call[1][0]) && $call[1][0]) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string[] $connectionNames
     *
     * @return array<string, string|array{primary: string, replicas: array<string, string>}>
     */
    private function getConnectionKeys(ContainerBuilder $container, array $connectionNames): array
    {
        /** @var array<string, string> $connectionKeys */
        $connectionKeys = $container->getParameter('dama.'.Configuration::CONNECTION_KEYS);
        $this->validateConnectionNames(array_keys($connectionKeys), $connectionNames);

        return $connectionKeys;
    }
}
