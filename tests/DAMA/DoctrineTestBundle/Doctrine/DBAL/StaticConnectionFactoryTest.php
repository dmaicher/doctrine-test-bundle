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
    public function testCreateConnection(bool $keepStaticConnections, array $params, int $expectedNestingLevel): void
    {
        $factory = new StaticConnectionFactory(new ConnectionFactory([]));

        StaticDriver::setKeepStaticConnections($keepStaticConnections);

        $connection = $factory->createConnection(array_merge($params, [
            'driverClass' => MockDriver::class,
        ]));

        if ($expectedNestingLevel > 0) {
            $this->assertInstanceOf(StaticDriver::class, $connection->getDriver());
        } else {
            $this->assertInstanceOf(MockDriver::class, $connection->getDriver());
        }

        $this->assertSame(0, $connection->getTransactionNestingLevel());

        $connection->connect();
        $this->assertSame($expectedNestingLevel, $connection->getTransactionNestingLevel());
    }

    public function createConnectionDataProvider(): \Generator
    {
        yield 'disabled by static property' => [
            false,
            ['dama.keep_static' => true],
            0,
        ];

        yield 'disabled by param' => [
            true,
            ['dama.keep_static' => false],
            0,
        ];

        yield 'enabled' => [
            true,
            ['dama.keep_static' => true, 'dama.connection_name' => 'a'],
            1,
        ];
    }
}
