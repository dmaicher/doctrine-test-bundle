<?php

namespace Tests\DAMA\DoctrineTestBundle\Doctrine\DBAL;

use DAMA\DoctrineTestBundle\Doctrine\DBAL\StaticConnectionFactory;
use DAMA\DoctrineTestBundle\Doctrine\DBAL\StaticDriver;
use Doctrine\Bundle\DoctrineBundle\ConnectionFactory;
use PHPUnit\Framework\TestCase;

class StaticConnectionFactoryTest extends TestCase
{
    /**
     * @dataProvider createConnectionDataProvider
     */
    public function testCreateConnection(bool $keepStaticConnections, array $params, string $driverClass): void
    {
        $factory = new StaticConnectionFactory(new ConnectionFactory([]));

        StaticDriver::setKeepStaticConnections($keepStaticConnections);

        $connection = $factory->createConnection(array_merge($params, [
            'driverClass' => MockDriver::class,
        ]));

        $this->assertInstanceOf($driverClass, $connection->getDriver());
        $this->assertSame(0, $connection->getTransactionNestingLevel());
    }

    public function createConnectionDataProvider(): \Generator
    {
        yield 'disabled by static property' => [
            false,
            ['dama.keep_static' => true],
            MockDriver::class,
        ];

        yield 'disabled by param' => [
            true,
            ['dama.keep_static' => false],
            MockDriver::class,
        ];

        yield 'enabled' => [
            true,
            ['dama.keep_static' => true, 'dama.connection_name' => 'a'],
            StaticDriver::class,
        ];
    }
}
