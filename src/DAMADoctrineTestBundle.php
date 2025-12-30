<?php

namespace DAMA\DoctrineTestBundle;

use DAMA\DoctrineTestBundle\DependencyInjection\AddMiddlewaresCompilerPass;
use DAMA\DoctrineTestBundle\DependencyInjection\ModifyDoctrineConfigCompilerPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * @final
 */
class DAMADoctrineTestBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
        // lower priority than CacheCompatibilityPass from DoctrineBundle
        $container->addCompilerPass(new ModifyDoctrineConfigCompilerPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, -1);

        // higher priority than MiddlewaresPass from DoctrineBundle
        $container->addCompilerPass(new AddMiddlewaresCompilerPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 1);
    }
}
