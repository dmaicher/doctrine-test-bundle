<?php

namespace tests\DAMA\DoctrineTestBundle\DependencyInjection;

use DAMA\DoctrineTestBundle\DependencyInjection\DAMADoctrineTestExtension;
use DAMA\DoctrineTestBundle\DependencyInjection\DoctrineTestCompilerPass;
use DAMA\DoctrineTestBundle\Doctrine\Cache\StaticArrayCache;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class DoctrineTestCompilerPassTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider processDataProvider
     *
     * @param array $processedConfig
     */
    public function testProcess(array $processedConfig)
    {
        $extension = $this->getMock(DAMADoctrineTestExtension::class);
        $extension
            ->expects($this->once())
            ->method('getProcessedConfig')
            ->willReturn($processedConfig)
        ;

        $containerBuilder = $this
            ->getMockBuilder(ContainerBuilder::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $containerBuilder
            ->expects($this->once())
            ->method('getExtension')
            ->with('dama_doctrine_test')
            ->willReturn($extension)
        ;

        if ($processedConfig['enable_static_connection']) {
            $containerBuilder
                ->expects($this->once())
                ->method('getDefinition')
                ->with('doctrine.dbal.connection_factory')
                ->willReturn(new Definition())
            ;
        } else {
            $containerBuilder
                ->expects($this->never())
                ->method('getDefinition')
                ->with('doctrine.dbal.connection_factory')
            ;
        }

        $containerBuilder
            ->expects($this->once())
            ->method('getParameter')
            ->with('doctrine.connections')
            ->willReturn(['a' => 0, 'b' => 1, 'c' => 2])
        ;

        $containerBuilder
            ->expects($this->exactly(6))
            ->method('hasAlias')
            ->withConsecutive(
                ['doctrine.orm.a_metadata_cache'],
                ['doctrine.orm.b_metadata_cache'],
                ['doctrine.orm.c_metadata_cache'],
                ['doctrine.orm.a_query_cache'],
                ['doctrine.orm.b_query_cache'],
                ['doctrine.orm.c_query_cache']
            )
            ->willReturn(true)
        ;

        $containerBuilder
            ->expects($this->exactly(6))
            ->method('removeAlias')
            ->withConsecutive(
                ['doctrine.orm.a_metadata_cache'],
                ['doctrine.orm.b_metadata_cache'],
                ['doctrine.orm.c_metadata_cache'],
                ['doctrine.orm.a_query_cache'],
                ['doctrine.orm.b_query_cache'],
                ['doctrine.orm.c_query_cache']
            )
        ;

        $containerBuilder
            ->expects($this->exactly(6))
            ->method('setDefinition')
            ->withConsecutive(
                [
                    'doctrine.orm.a_metadata_cache',
                    (new Definition(StaticArrayCache::class))->addMethodCall('setNamespace', [sha1('doctrine.orm.a_metadata_cache')]),
                ],
                [
                    'doctrine.orm.b_metadata_cache',
                    (new Definition(StaticArrayCache::class))->addMethodCall('setNamespace', [sha1('doctrine.orm.b_metadata_cache')]),
                ],
                [
                    'doctrine.orm.c_metadata_cache',
                    (new Definition(StaticArrayCache::class))->addMethodCall('setNamespace', [sha1('doctrine.orm.c_metadata_cache')]),
                ],
                [
                    'doctrine.orm.a_query_cache',
                    (new Definition(StaticArrayCache::class))->addMethodCall('setNamespace', [sha1('doctrine.orm.a_query_cache')]),
                ],
                [
                    'doctrine.orm.b_query_cache',
                    (new Definition(StaticArrayCache::class))->addMethodCall('setNamespace', [sha1('doctrine.orm.b_query_cache')]),
                ],
                [
                    'doctrine.orm.c_query_cache',
                    (new Definition(StaticArrayCache::class))->addMethodCall('setNamespace', [sha1('doctrine.orm.c_query_cache')]),
                ]
            )
        ;

        (new DoctrineTestCompilerPass())->process($containerBuilder);
    }

    public function processDataProvider()
    {
        return [
            [
                [
                    'enable_static_connection' => true,
                    'enable_static_meta_data_cache' => true,
                    'enable_static_query_cache' => true,
                ],
                [
                    'enable_static_connection' => false,
                    'enable_static_meta_data_cache' => true,
                    'enable_static_query_cache' => true,
                ],
            ],
        ];
    }
}
