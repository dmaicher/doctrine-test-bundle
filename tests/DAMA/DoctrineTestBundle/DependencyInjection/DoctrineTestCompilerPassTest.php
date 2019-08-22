<?php

namespace Tests\DAMA\DoctrineTestBundle\DependencyInjection;

use DAMA\DoctrineTestBundle\DependencyInjection\DAMADoctrineTestExtension;
use DAMA\DoctrineTestBundle\DependencyInjection\DoctrineTestCompilerPass;
use DAMA\DoctrineTestBundle\Doctrine\Cache\StaticArrayCache;
use DAMA\DoctrineTestBundle\Doctrine\DBAL\StaticConnectionFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class DoctrineTestCompilerPassTest extends TestCase
{
    public function testProcess(): void
    {
        $extension = $this->createMock(DAMADoctrineTestExtension::class);
        $extension
            ->expects($this->once())
            ->method('getProcessedConfig')
            ->willReturn([
                'enable_static_connection' => true,
                'enable_static_meta_data_cache' => true,
                'enable_static_query_cache' => true,
            ])
        ;

        /** @var ContainerBuilder|MockObject $containerBuilder */
        $containerBuilder = $this->createMock(ContainerBuilder::class);
        $containerBuilder
            ->expects($this->once())
            ->method('getExtension')
            ->with('dama_doctrine_test')
            ->willReturn($extension)
        ;

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
            ->expects($this->exactly(7))
            ->method('setDefinition')
            ->withConsecutive(
                [
                    'dama.doctrine.dbal.connection_factory',
                    (new Definition(StaticConnectionFactory::class))
                        ->setDecoratedService('doctrine.dbal.connection_factory')
                        ->addArgument('dama.doctrine.dbal.connection_factory.inner'),
                ],
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
}
