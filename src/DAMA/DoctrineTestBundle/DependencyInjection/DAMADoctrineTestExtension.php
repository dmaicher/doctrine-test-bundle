<?php

namespace DAMA\DoctrineTestBundle\DependencyInjection;

use DAMA\DoctrineTestBundle\Doctrine\DBAL\PostConnectEventListener;
use Doctrine\DBAL\Events;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class DAMADoctrineTestExtension extends Extension
{
    /**
     * @var array
     */
    private $processedConfig;

    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $this->processedConfig = $this->processConfiguration($configuration, $configs);

        $postConnectListenerDef = new Definition(PostConnectEventListener::class);
        $postConnectListenerDef->addTag('doctrine.event_listener', ['event' => Events::postConnect]);
        $container->setDefinition('dama.doctrine.dbal.post_connect_event_listener', $postConnectListenerDef);
    }

    /**
     * @internal
     */
    public function getProcessedConfig(): array
    {
        return $this->processedConfig;
    }
}
