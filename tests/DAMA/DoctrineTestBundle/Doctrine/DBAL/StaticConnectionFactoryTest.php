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
    public function testCreateConnection(bool $defaultKeepStaticConnections, ?bool $keepStaticConnections, array $params, int $expectedNestingLevel): void
    {
        $factory = new StaticConnectionFactory(new ConnectionFactory([]), $defaultKeepStaticConnections);

        if (null !== $keepStaticConnections) {
            StaticDriver::setKeepStaticConnections($keepStaticConnections);
        }

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
        yield 'disabled by default' => [
            false,
            null,
            ['dama.keep_static' => true, 'dama.connection_name' => 'a'],
            0,
        ];

        yield 'disabled by static property' => [
            true,
            false,
            ['dama.keep_static' => true],
            0,
        ];

        yield 'disabled by param' => [
            true,
            true,
            ['dama.keep_static' => false],
            0,
        ];

        yield 'enabled by default' => [
            true,
            null,
            ['dama.keep_static' => true, 'dama.connection_name' => 'a'],
            1,
        ];

        yield 'enabled' => [
            false,
            true,
            ['dama.keep_static' => true, 'dama.connection_name' => 'a'],
            1,
        ];
    }
}
