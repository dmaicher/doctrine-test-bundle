<?php

namespace Tests\Functional\app;

use DAMA\DoctrineTestBundle\DAMADoctrineTestBundle;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;

class AppKernel extends Kernel
{
    public function registerBundles(): array
    {
        return [
            new FrameworkBundle(),
            new DoctrineBundle(),
            new DAMADoctrineTestBundle(),
        ];
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load(__DIR__.'/config.yml');
    }

    protected function build(ContainerBuilder $container): void
    {
        $container->register('logger', NullLogger::class);
    }
}
