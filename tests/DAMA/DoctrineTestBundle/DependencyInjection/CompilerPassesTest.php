<?php

declare(strict_types=1);

namespace Tests\DAMA\DoctrineTestBundle\DependencyInjection;

use DAMA\DoctrineTestBundle\DependencyInjection\AddMiddlewaresCompilerPass;
use DAMA\DoctrineTestBundle\DependencyInjection\DAMADoctrineTestExtension;
use DAMA\DoctrineTestBundle\DependencyInjection\ModifyDoctrineConfigCompilerPass;
use DAMA\DoctrineTestBundle\Doctrine\Cache\Psr6StaticArrayCache;
use Doctrine\Bundle\DoctrineBundle\ConnectionFactory;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class CompilerPassesTest extends TestCase
{
    private const CACHE_SERVICE_IDS = [
        'doctrine.orm.a_metadata_cache',
        'doctrine.orm.b_metadata_cache',
        'doctrine.orm.c_metadata_cache',
        'doctrine.orm.a_query_cache',
        'doctrine.orm.b_query_cache',
        'doctrine.orm.c_query_cache',
    ];

    #[DataProvider('processDataProvider')]
    public function testProcess(array $config, callable $assertCallback, ?callable $expectationCallback = null): void
    {
        $containerBuilder = new ContainerBuilder();
        $extension = new DAMADoctrineTestExtension();
        $containerBuilder->registerExtension($extension);

        $extension->load([$config], $containerBuilder);

        $containerBuilder->setParameter('doctrine.connections', ['a' => 0, 'b' => 1, 'c' => 2]);

        $containerBuilder->setDefinition('doctrine.dbal.a_connection', new Definition(Connection::class, [[]]));
        $containerBuilder->setDefinition('doctrine.dbal.b_connection', new Definition(Connection::class, [[]]));
        $containerBuilder->setDefinition('doctrine.dbal.c_connection', new Definition(Connection::class, [[
            'primary' => [],
            'replica' => [
                'one' => [],
                'two' => [],
            ],
        ]]));

        foreach (['a', 'b', 'c'] as $name) {
            $containerBuilder->getDefinition(sprintf('doctrine.dbal.%s_connection', $name))
                ->addMethodCall('setNestTransactionsWithSavepoints', [true])
            ;
        }

        $containerBuilder->setDefinition('doctrine.dbal.b_connection.configuration', new Definition(Configuration::class));
        $containerBuilder->setDefinition('doctrine.dbal.c_connection.configuration', new Definition(Configuration::class));

        $containerBuilder->setDefinition('doctrine.dbal.connection_factory', new Definition(ConnectionFactory::class));

        if ($expectationCallback !== null) {
            $expectationCallback($this, $containerBuilder);
        }

        (new AddMiddlewaresCompilerPass())->process($containerBuilder);
        (new ModifyDoctrineConfigCompilerPass())->process($containerBuilder);

        foreach (array_keys($containerBuilder->getParameterBag()->all()) as $parameterName) {
            $this->assertStringStartsNotWith('dama.', $parameterName);
        }

        $assertCallback($containerBuilder);
    }

    public static function processDataProvider(): \Generator
    {
        $defaultConfig = [
            'enable_static_connection' => true,
            'enable_static_meta_data_cache' => true,
            'enable_static_query_cache' => true,
        ];

        yield 'default config' => [
            $defaultConfig,
            function (ContainerBuilder $containerBuilder): void {
                foreach (self::CACHE_SERVICE_IDS as $id) {
                    self::assertFalse($containerBuilder->hasAlias($id));
                    self::assertFalse($containerBuilder->hasDefinition($id));
                }

                self::assertSame([
                    'dama.connection_key' => 'a',
                ], $containerBuilder->getDefinition('doctrine.dbal.a_connection')->getArgument(0));
            },
        ];

        yield 'disabled' => [
            [
                'enable_static_connection' => false,
                'enable_static_meta_data_cache' => false,
                'enable_static_query_cache' => false,
            ],
            function (ContainerBuilder $containerBuilder): void {
                self::assertFalse($containerBuilder->hasDefinition('doctrine.orm.a_metadata_cache'));
            },
        ];

        yield 'enabled per connection' => [
            [
                'enable_static_connection' => [
                    'a' => true,
                    'c' => true,
                ],
                'enable_static_meta_data_cache' => true,
                'enable_static_query_cache' => true,
            ],
            function (ContainerBuilder $containerBuilder): void {
                self::assertSame([
                    'dama.connection_key' => 'a',
                ], $containerBuilder->getDefinition('doctrine.dbal.a_connection')->getArgument(0));
                self::assertTrue($containerBuilder->hasDefinition('dama.doctrine.dbal.middleware.a'));
                self::assertSame([
                    'connection' => 'a',
                    'priority' => 100,
                ], $containerBuilder->getDefinition('dama.doctrine.dbal.middleware.a')->getTag('doctrine.middleware')[0]);

                self::assertSame([], $containerBuilder->getDefinition('doctrine.dbal.b_connection')->getArgument(0));
                self::assertFalse($containerBuilder->hasDefinition('dama.doctrine.dbal.middleware.b'));

                self::assertSame(
                    [
                        'primary' => [
                            'dama.connection_key' => 'c',
                        ],
                        'replica' => [
                            'one' => [
                                'dama.connection_key' => 'c',
                            ],
                            'two' => [
                                'dama.connection_key' => 'c',
                            ],
                        ],
                        'dama.connection_key' => 'c',
                    ],
                    $containerBuilder->getDefinition('doctrine.dbal.c_connection')->getArgument(0)
                );
                self::assertTrue($containerBuilder->hasDefinition('dama.doctrine.dbal.middleware.c'));
            },
        ];

        yield 'invalid connection names' => [
            [
                'enable_static_connection' => [
                    'foo' => true,
                    'bar' => true,
                ],
                'enable_static_meta_data_cache' => false,
                'enable_static_query_cache' => false,
            ],
            function (ContainerBuilder $containerBuilder): void {
            },
            function (TestCase $testCase): void {
                $testCase->expectException(\InvalidArgumentException::class);
                $testCase->expectExceptionMessage('Unknown doctrine dbal connection name(s): foo, bar.');
            },
        ];

        yield 'Custom keys' => [
            [
                'connection_keys' => [
                    'a' => 'key_1',
                    'b' => 'key_2',
                    'c' => 'key_3',
                ],
            ],
            function (ContainerBuilder $containerBuilder): void {
                self::assertSame([
                    'dama.connection_key' => 'key_1',
                ], $containerBuilder->getDefinition('doctrine.dbal.a_connection')->getArgument(0));

                self::assertSame([
                    'dama.connection_key' => 'key_2',
                ], $containerBuilder->getDefinition('doctrine.dbal.b_connection')->getArgument(0));

                self::assertSame(
                    [
                        'primary' => [
                            'dama.connection_key' => 'key_3',
                        ],
                        'replica' => [
                            'one' => [
                                'dama.connection_key' => 'key_3',
                            ],
                            'two' => [
                                'dama.connection_key' => 'key_3',
                            ],
                        ],
                        'dama.connection_key' => 'key_3',
                    ],
                    $containerBuilder->getDefinition('doctrine.dbal.c_connection')->getArgument(0)
                );
            },
        ];

        yield 'Custom keys for primary/replica' => [
            [
                'connection_keys' => [
                    'c' => [
                        'primary' => 'key_3',
                        'replicas' => [
                            'one' => 'key_4',
                        ],
                    ],
                ],
            ],
            function (ContainerBuilder $containerBuilder): void {
                self::assertSame([
                    'dama.connection_key' => 'a',
                ], $containerBuilder->getDefinition('doctrine.dbal.a_connection')->getArgument(0));

                self::assertSame(
                    [
                        'primary' => [
                            'dama.connection_key' => 'key_3',
                        ],
                        'replica' => [
                            'one' => [
                                'dama.connection_key' => 'key_4',
                            ],
                            'two' => [
                                'dama.connection_key' => 'key_3',
                            ],
                        ],
                        'dama.connection_key' => 'key_3',
                    ],
                    $containerBuilder->getDefinition('doctrine.dbal.c_connection')->getArgument(0)
                );
            },
        ];

        yield 'psr6 ORM cache services' => [
            $defaultConfig,
            function (ContainerBuilder $containerBuilder): void {
                foreach (self::CACHE_SERVICE_IDS as $id) {
                    self::assertFalse($containerBuilder->hasAlias($id));
                    self::assertEquals(
                        (new Definition(Psr6StaticArrayCache::class))->setArgument(0, sha1($id)),
                        $containerBuilder->getDefinition($id)
                    );
                }
            },
            function (self $testCase, ContainerBuilder $containerBuilder): void {
                foreach (self::CACHE_SERVICE_IDS as $id) {
                    $containerBuilder->register($id, ArrayAdapter::class);
                }
            },
        ];

        yield 'psr6 ORM cache services using child definitions' => [
            $defaultConfig,
            function (ContainerBuilder $containerBuilder): void {
                foreach (self::CACHE_SERVICE_IDS as $id) {
                    self::assertFalse($containerBuilder->hasAlias($id));
                    self::assertEquals(
                        (new Definition(Psr6StaticArrayCache::class))->setArgument(0, sha1($id)),
                        $containerBuilder->getDefinition($id)
                    );
                }
            },
            function (self $testCase, ContainerBuilder $containerBuilder): void {
                $parentDefinition = new Definition(ArrayAdapter::class);
                $containerBuilder->setDefinition('some_cache_parent_definition', $parentDefinition);
                foreach (self::CACHE_SERVICE_IDS as $id) {
                    $containerBuilder->setDefinition($id, new ChildDefinition('some_cache_parent_definition'));
                }
            },
        ];

        yield 'invalid ORM cache services' => [
            $defaultConfig,
            function (ContainerBuilder $containerBuilder): void {
            },
            function (self $testCase, ContainerBuilder $containerBuilder): void {
                $testCase->expectException(\InvalidArgumentException::class);
                $testCase->expectExceptionMessage('Unsupported cache class "stdClass" found on service "doctrine.orm.a_metadata_cache"');
                foreach (self::CACHE_SERVICE_IDS as $id) {
                    $containerBuilder->register($id, \stdClass::class);
                }
            },
        ];
    }
}
