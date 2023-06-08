<?php

namespace Tests\DAMA\DoctrineTestBundle\DependencyInjection;

use DAMA\DoctrineTestBundle\DependencyInjection\DAMADoctrineTestExtension;
use DAMA\DoctrineTestBundle\DependencyInjection\DoctrineTestCompilerPass;
use DAMA\DoctrineTestBundle\Doctrine\Cache\Psr6StaticArrayCache;
use DAMA\DoctrineTestBundle\Doctrine\Cache\StaticArrayCache;
use Doctrine\Bundle\DoctrineBundle\ConnectionFactory;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class DoctrineTestCompilerPassTest extends TestCase
{
    private const CACHE_SERVICE_IDS = [
        'doctrine.orm.a_metadata_cache',
        'doctrine.orm.b_metadata_cache',
        'doctrine.orm.c_metadata_cache',
        'doctrine.orm.a_query_cache',
        'doctrine.orm.b_query_cache',
        'doctrine.orm.c_query_cache',
    ];

    /**
     * @dataProvider processDataProvider
     */
    public function testProcess(array $config, callable $assertCallback, callable $expectationCallback = null): void
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

        $containerBuilder->setDefinition(
            'doctrine.dbal.a_connection.configuration',
            (new Definition(Configuration::class))
                ->setMethodCalls([['setMiddlewares', [[new Reference('foo')]]]])
        );
        $containerBuilder->setDefinition('doctrine.dbal.b_connection.configuration', new Definition(Configuration::class));
        $containerBuilder->setDefinition('doctrine.dbal.c_connection.configuration', new Definition(Configuration::class));

        $containerBuilder->setDefinition('doctrine.dbal.connection_factory', new Definition(ConnectionFactory::class));

        if ($expectationCallback !== null) {
            $expectationCallback($this, $containerBuilder);
        }

        (new DoctrineTestCompilerPass())->process($containerBuilder);

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
                    'dama.keep_static' => true,
                    'dama.connection_name' => 'a',
                ], $containerBuilder->getDefinition('doctrine.dbal.a_connection')->getArgument(0));

                self::assertEquals(
                    [
                        [
                            'setMiddlewares',
                            [
                                [
                                    new Reference('dama.doctrine.dbal.middleware'),
                                    new Reference('foo'),
                                ],
                            ],
                        ],
                    ],
                    $containerBuilder->getDefinition('doctrine.dbal.a_connection.configuration')->getMethodCalls()
                );

                self::assertEquals(
                    [
                        [
                            'setMiddlewares',
                            [
                                [
                                    new Reference('dama.doctrine.dbal.middleware'),
                                ],
                            ],
                        ],
                    ],
                    $containerBuilder->getDefinition('doctrine.dbal.b_connection.configuration')->getMethodCalls()
                );
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

                self::assertEquals(
                    [
                        [
                            'setMiddlewares',
                            [
                                [
                                    new Reference('foo'),
                                ],
                            ],
                        ],
                    ],
                    $containerBuilder->getDefinition('doctrine.dbal.a_connection.configuration')->getMethodCalls()
                );
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
                    'dama.keep_static' => true,
                    'dama.connection_name' => 'a',
                ], $containerBuilder->getDefinition('doctrine.dbal.a_connection')->getArgument(0));

                self::assertSame([], $containerBuilder->getDefinition('doctrine.dbal.b_connection')->getArgument(0));

                self::assertSame(
                    [
                        'primary' => [
                            'dama.keep_static' => true,
                            'dama.connection_name' => 'c',
                        ],
                        'replica' => [
                            'one' => [
                                'dama.keep_static' => true,
                                'dama.connection_name' => 'c.one',
                            ],
                            'two' => [
                                'dama.keep_static' => true,
                                'dama.connection_name' => 'c.two',
                            ],
                        ],
                        'dama.keep_static' => true,
                        'dama.connection_name' => 'c',
                    ],
                    $containerBuilder->getDefinition('doctrine.dbal.c_connection')->getArgument(0)
                );
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

        yield 'legacy doctrine/cache ORM services' => [
            $defaultConfig,
            function (ContainerBuilder $containerBuilder): void {
                foreach (self::CACHE_SERVICE_IDS as $id) {
                    self::assertFalse($containerBuilder->hasAlias($id));
                    self::assertEquals(
                        (new Definition(StaticArrayCache::class))->addMethodCall('setNamespace', [sha1($id)]),
                        $containerBuilder->getDefinition($id)
                    );
                }
            },
            function (self $testCase, ContainerBuilder $containerBuilder): void {
                foreach (self::CACHE_SERVICE_IDS as $id) {
                    $containerBuilder->register($id, ArrayCache::class);
                }
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
