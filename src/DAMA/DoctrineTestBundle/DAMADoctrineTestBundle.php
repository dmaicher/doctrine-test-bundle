<?php

namespace DAMA\DoctrineTestBundle;

use DAMA\DoctrineTestBundle\DependencyInjection\DoctrineTestCompilerPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class DAMADoctrineTestBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
        // lower priority than CacheCompatibilityPass from DoctrineBundle
        $container->addCompilerPass(new DoctrineTestCompilerPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, -1);
    }
}
