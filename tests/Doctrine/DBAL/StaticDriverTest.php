<?php

declare(strict_types=1);

namespace Tests\Doctrine\DBAL;

use DAMA\DoctrineTestBundle\Doctrine\DBAL\StaticConnection;
use DAMA\DoctrineTestBundle\Doctrine\DBAL\StaticDriver;
use Doctrine\DBAL\Driver\API\ExceptionConverter;
use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use PHPUnit\Framework\TestCase;

class StaticDriverTest extends TestCase
{
    private MockDriver $driver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->driver = new MockDriver(
            $this->createStub(Connection::class),
            $this->createStub(AbstractSchemaManager::class),
            $this->createStub(ExceptionConverter::class),
        );
    }

    public function testConnect(): void
    {
        $driver = new StaticDriver($this->driver);

        $driver::setKeepStaticConnections(true);

        $params = [
            'driver' => 'pdo_mysql',
            'charset' => 'UTF8',
            'host' => 'foo',
            'dbname' => 'doctrine_test_bundle',
            'user' => 'user',
            'password' => 'password',
            'port' => 3306,
            'dama.connection_key' => 'foo',
            'some_closure' => function (): void {},
        ];

        /** @var StaticConnection $connection1 */
        $connection1 = $driver->connect(['dama.connection_key' => 'foo'] + $params);
        /** @var StaticConnection $connection2 */
        $connection2 = $driver->connect(['dama.connection_key' => 'bar'] + $params);

        $this->assertInstanceOf(StaticConnection::class, $connection1);
        $this->assertNotSame($connection1->getWrappedConnection(), $connection2->getWrappedConnection());

        $driver = new StaticDriver($this->driver);

        /** @var StaticConnection $connectionNew1 */
        $connectionNew1 = $driver->connect(['dama.connection_key' => 'foo'] + $params);
        /** @var StaticConnection $connectionNew2 */
        $connectionNew2 = $driver->connect(['dama.connection_key' => 'bar'] + $params);

        $this->assertSame($connection1->getWrappedConnection(), $connectionNew1->getWrappedConnection());
        $this->assertSame($connection2->getWrappedConnection(), $connectionNew2->getWrappedConnection());

        /** @var StaticConnection $connection1 */
        $connection1 = $driver->connect($params);
        /** @var StaticConnection $connection2 */
        $connection2 = $driver->connect($params);
        $this->assertSame($connection1->getWrappedConnection(), $connection2->getWrappedConnection());
    }
}
