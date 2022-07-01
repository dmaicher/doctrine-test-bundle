<?php

namespace DAMA\DoctrineTestBundle\DependencyInjection;

use DAMA\DoctrineTestBundle\Doctrine\DBAL\PostConnectEventListener;
use Doctrine\DBAL\Events;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

final class RegisterDoctrineEventListenersPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
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
                $postConnectListenerDef = new Definition(PostConnectEventListener::class);
                $postConnectListenerDef->addTag(
                    'doctrine.event_listener',
                    ['event' => Events::postConnect, 'connection' => $name, 'priority' => 1000]
                );
                $container->setDefinition('dama.doctrine.dbal.post_connect_event_listener.'.$name, $postConnectListenerDef);
            }
        }

        $container->setParameter('dama.doctrine.enabled_connections', $enabledConnections);
        $container->getParameterBag()->remove('dama.'.Configuration::ENABLE_STATIC_CONNECTION);
    }

    private function validateConnectionNames(array $configNames, array $existingNames): void
    {
        $unknown = array_diff($configNames, $existingNames);

        if (count($unknown)) {
            throw new \InvalidArgumentException(sprintf('Unknown doctrine dbal connection name(s): %s.', implode(', ', $unknown)));
        }
    }
}
