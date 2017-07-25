<?php

namespace Tests\DAMA\DoctrineTestBundle\DependencyInjection;

use DAMA\DoctrineTestBundle\DependencyInjection\DAMADoctrineTestExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class DAMADoctrineTestExtensionTest extends TestCase
{
    /**
     * @dataProvider loadDataProvider
     *
     * @param array $configs
     * @param array $expectedProcessedConfig
     */
    public function testLoad(array $configs, array $expectedProcessedConfig)
    {
        $extension = new DAMADoctrineTestExtension();
        $containerBuilder = $this
            ->getMockBuilder(ContainerBuilder::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $extension->load($configs, $containerBuilder);

        $this->assertEquals($extension->getProcessedConfig(), $expectedProcessedConfig);
    }

    public function loadDataProvider()
    {
        return [
            [[], [
                'enable_static_connection' => true,
                'enable_static_meta_data_cache' => true,
                'enable_static_query_cache' => true,
            ]],
            [
                [
                    [
                        'enable_static_connection' => false,
                    ],
                    [
                        'enable_static_meta_data_cache' => false,
                        'enable_static_query_cache' => false,
                    ],
                ],
                [
                    'enable_static_connection' => false,
                    'enable_static_meta_data_cache' => false,
                    'enable_static_query_cache' => false,
                ],
            ],
        ];
    }
}
