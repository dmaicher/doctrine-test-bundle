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
    public function testCreateConnection(bool $keepStaticConnections, array $params, bool $expectedNestingWithSavepoints): void
    {
        $factory = new StaticConnectionFactory(new ConnectionFactory([]));

        StaticDriver::setKeepStaticConnections($keepStaticConnections);

        $connection = $factory->createConnection(array_merge($params, [
            'driverClass' => MockDriver::class,
        ]));

        $this->assertInstanceOf(MockDriver::class, $connection->getDriver());

        $this->assertSame($expectedNestingWithSavepoints, $connection->getNestTransactionsWithSavepoints());
    }

    public static function createConnectionDataProvider(): \Generator
    {
        yield 'disabled by static property' => [
            false,
            ['dama.keep_static' => true],
            false,
        ];

        yield 'disabled by param' => [
            true,
            ['dama.keep_static' => false],
            false,
        ];

        yield 'enabled' => [
            true,
            ['dama.keep_static' => true, 'dama.connection_name' => 'a'],
            true,
        ];
    }
}
