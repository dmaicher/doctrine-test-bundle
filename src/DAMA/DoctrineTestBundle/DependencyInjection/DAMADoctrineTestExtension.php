<?php

namespace DAMA\DoctrineTestBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
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
    }

    public function getProcessedConfig(): array
    {
        return $this->processedConfig;
    }
}
