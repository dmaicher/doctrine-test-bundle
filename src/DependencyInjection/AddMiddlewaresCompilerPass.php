<?php

declare(strict_types=1);

namespace DAMA\DoctrineTestBundle\DependencyInjection;

use DAMA\DoctrineTestBundle\Doctrine\DBAL\Middleware;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class AddMiddlewaresCompilerPass implements CompilerPassInterface
{
    public const TRANSACTIONAL_BEHAVIOR_ENABLED_CONNECTIONS = 'dama.doctrine_test.transactional_behavior_enabled_connections';

    public function process(ContainerBuilder $container): void
    {
        /** @var array<string, mixed> $connections */
        $connections = $container->getParameter('doctrine.connections');
        $connectionNames = array_keys($connections);
        $transactionalBehaviorEnabledConnections = $this->getTransactionEnabledConnectionNames($container, $connectionNames);
        $container->getParameterBag()->set(self::TRANSACTIONAL_BEHAVIOR_ENABLED_CONNECTIONS, $transactionalBehaviorEnabledConnections);

        foreach ($transactionalBehaviorEnabledConnections as $name) {
            $middlewareDefinition = $container->register(sprintf('dama.doctrine.dbal.middleware.%s', $name), Middleware::class);
            $middlewareDefinition->addTag('doctrine.middleware', ['connection' => $name, 'priority' => 100]);
        }

        $container->getParameterBag()->remove('dama.'.Configuration::ENABLE_STATIC_CONNECTION);
    }

    /**
     * @param string[] $connectionNames
     *
     * @return string[]
     */
    private function getTransactionEnabledConnectionNames(ContainerBuilder $container, array $connectionNames): array
    {
        /** @var bool|array<string, bool> $enableStaticConnectionsConfig */
        $enableStaticConnectionsConfig = $container->getParameter('dama.'.Configuration::ENABLE_STATIC_CONNECTION);

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
