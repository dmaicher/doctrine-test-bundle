<?php

declare(strict_types=1);

namespace Tests\Functional\app;

use DAMA\DoctrineTestBundle\DAMADoctrineTestBundle;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\HttpKernel\Kernel;

class AppKernel extends Kernel
{
    /**
     * @return array<Bundle>
     */
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
        $loader->load(function (ContainerBuilder $builder): void {
            $builder->setParameter('kernel.secret', 'foo');

            $builder->loadFromExtension('framework', [
                'http_method_override' => false,
            ]);

            $builder->loadFromExtension('doctrine', [
                'dbal' => [
                    'connections' => [
                        'default' => [
                            'url' => '%env(DATABASE_URL)%',
                            'server_version' => '8.4.0',
                        ],
                        'replica' => [
                            'url' => '%env(DATABASE_URL)%',
                            'replicas' => [
                                'replica_one' => [
                                    'url' => '%env(DATABASE_URL)%',
                                ],
                            ],
                        ],
                    ],
                ],
            ]);

            // TODO remove once we drop support for DoctrineBundle < 3
            if (class_exists('Doctrine\Bundle\DoctrineBundle\Command\ImportMappingDoctrineCommand')) {
                $builder->loadFromExtension('doctrine', [
                    'dbal' => [
                        'connections' => [
                            'default' => ['use_savepoints' => true],
                            'replica' => ['use_savepoints' => true],
                        ],
                    ],
                ]);
            }

            $builder->loadFromExtension('dama_doctrine_test', [
                'enable_static_connection' => true,
                'enable_static_meta_data_cache' => true,
                'enable_static_query_cache' => true,
                'connection_keys' => [
                    'default' => 'custom_key',
                ],
            ]);
        });
    }

    protected function build(ContainerBuilder $container): void
    {
        $container->register('logger', NullLogger::class);
    }
}
