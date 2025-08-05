<?php

namespace DAMA\DoctrineTestBundle\Behat\ServiceContainer;

use Behat\Testwork\EventDispatcher\ServiceContainer\EventDispatcherExtension;
use Behat\Testwork\ServiceContainer\Extension;
use Behat\Testwork\ServiceContainer\ExtensionManager;
use DAMA\DoctrineTestBundle\Behat\BehatListener;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class DoctrineExtension implements Extension
{
    public function getConfigKey(): string
    {
        return 'dama_doctrine';
    }

    public function initialize(ExtensionManager $extensionManager): void
    {
    }

    public function configure(ArrayNodeDefinition $builder): void
    {
    }

    public function load(ContainerBuilder $container, array $config): void
    {
        $container->register('dama_doctrine_test.listener', BehatListener::class)
            ->addTag(EventDispatcherExtension::SUBSCRIBER_TAG)
        ;
    }

    public function process(ContainerBuilder $container): void
    {
    }
}
