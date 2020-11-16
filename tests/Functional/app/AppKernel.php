<?php

namespace Tests\Functional\app;

use Psr\Log\NullLogger;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;

class AppKernel extends Kernel
{
    public function registerBundles(): array
    {
        return [
            new \Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new \Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            new \DAMA\DoctrineTestBundle\DAMADoctrineTestBundle(),
        ];
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load(__DIR__.'/config.yml');
    }

    protected function build(ContainerBuilder $container): void
    {
        $container->register('logger', NullLogger::class);
        $container->addCompilerPass(new class() implements CompilerPassInterface {
            public function process(ContainerBuilder $container): void
            {
                // until https://github.com/doctrine/DoctrineBundle/pull/1263 is released on 1.12.x as well
                $container->getDefinition('doctrine.dbal.logger.chain.default')->removeMethodCall('addLogger');
                $container->getDefinition('doctrine.dbal.logger.chain')->removeMethodCall('addLogger');
            }
        });
    }
}
