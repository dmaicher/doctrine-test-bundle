<?php

namespace Tests\DAMA\DoctrineTestBundle\DependencyInjection;

use DAMA\DoctrineTestBundle\DependencyInjection\DAMADoctrineTestExtension;
use DAMA\DoctrineTestBundle\DependencyInjection\DoctrineTestCompilerPass;
use DAMA\DoctrineTestBundle\Doctrine\Cache\StaticArrayCache;
use Doctrine\Bundle\DoctrineBundle\ConnectionFactory;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class DoctrineTestCompilerPassTest extends TestCase
{
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
        $containerBuilder->setDefinition('doctrine.dbal.c_connection', new Definition(Connection::class, [[]]));
        $containerBuilder->setDefinition('doctrine.dbal.connection_factory', new Definition(ConnectionFactory::class));

        if ($expectationCallback !== null) {
            $expectationCallback($this);
        }

        (new DoctrineTestCompilerPass())->process($containerBuilder);

        $assertCallback($containerBuilder);
    }

    public function processDataProvider(): \Generator
    {
        yield 'default config' => [
            [
                'enable_static_connection' => true,
                'enable_static_meta_data_cache' => true,
                'enable_static_query_cache' => true,
            ],
            function (ContainerBuilder $containerBuilder): void {
                $this->assertTrue($containerBuilder->hasDefinition('dama.doctrine.dbal.connection_factory'));
                $this->assertSame(
                    'doctrine.dbal.connection_factory',
                    $containerBuilder->getDefinition('dama.doctrine.dbal.connection_factory')->getDecoratedService()[0]
                );

                $cacheServiceIds = [
                    'doctrine.orm.a_metadata_cache',
                    'doctrine.orm.b_metadata_cache',
                    'doctrine.orm.c_metadata_cache',
                    'doctrine.orm.a_query_cache',
                    'doctrine.orm.b_metadata_cache',
                    'doctrine.orm.c_metadata_cache',
                ];

                foreach ($cacheServiceIds as $id) {
                    $this->assertFalse($containerBuilder->hasAlias($id));
                    $this->assertEquals(
                        (new Definition(StaticArrayCache::class))->addMethodCall('setNamespace', [sha1($id)]),
                        $containerBuilder->getDefinition($id)
                    );
                }

                $this->assertSame([
                    'dama.keep_static' => true,
                    'dama.connection_name' => 'a',
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
                $this->assertFalse($containerBuilder->hasDefinition('dama.doctrine.dbal.connection_factory'));
                $this->assertFalse($containerBuilder->hasDefinition('doctrine.orm.a_metadata_cache'));
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
                $this->assertTrue($containerBuilder->hasDefinition('dama.doctrine.dbal.connection_factory'));

                $this->assertSame([
                    'dama.keep_static' => true,
                    'dama.connection_name' => 'a',
                ], $containerBuilder->getDefinition('doctrine.dbal.a_connection')->getArgument(0));

                $this->assertSame([], $containerBuilder->getDefinition('doctrine.dbal.b_connection')->getArgument(0));

                $this->assertSame([
                    'dama.keep_static' => true,
                    'dama.connection_name' => 'c',
                ], $containerBuilder->getDefinition('doctrine.dbal.c_connection')->getArgument(0));
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
    }
}
