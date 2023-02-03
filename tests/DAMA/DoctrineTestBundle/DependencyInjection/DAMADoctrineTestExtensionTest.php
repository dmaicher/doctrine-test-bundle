<?php

namespace Tests\DAMA\DoctrineTestBundle\DependencyInjection;

use DAMA\DoctrineTestBundle\DependencyInjection\DAMADoctrineTestExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class DAMADoctrineTestExtensionTest extends TestCase
{
    /**
     * @dataProvider loadDataProvider
     */
    public function testLoad(array $configs, array $expectedParameters): void
    {
        $extension = new DAMADoctrineTestExtension();
        $containerBuilder = new ContainerBuilder();
        $extension->load($configs, $containerBuilder);

        foreach ($expectedParameters as $name => $value) {
            $this->assertSame($value, $containerBuilder->getParameter('dama.'.$name));
        }
    }

    public static function loadDataProvider(): array
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
            [[
                [
                    'enable_static_connection' => [
                        'a' => true,
                        'b' => false,
                    ],
                ],
            ], [
                'enable_static_connection' => [
                    'a' => true,
                    'b' => false,
                ],
                'enable_static_meta_data_cache' => true,
                'enable_static_query_cache' => true,
            ]],
        ];
    }
}
