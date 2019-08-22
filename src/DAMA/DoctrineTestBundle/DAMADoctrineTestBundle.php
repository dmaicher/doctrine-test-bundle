<?php

namespace DAMA\DoctrineTestBundle;

use DAMA\DoctrineTestBundle\DependencyInjection\DoctrineTestCompilerPass;
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
        $container->addCompilerPass(new DoctrineTestCompilerPass());
    }
}
