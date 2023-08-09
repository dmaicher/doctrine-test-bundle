<?php

namespace Tests\DAMA\DoctrineTestBundle\Doctrine\DBAL;

use DAMA\DoctrineTestBundle\Doctrine\DBAL\StaticConnection;
use DAMA\DoctrineTestBundle\Doctrine\DBAL\StaticDriver;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use PHPUnit\Framework\TestCase;

class StaticDriverTest extends TestCase
{
    public function testConnect(): void
    {
        $driver = new StaticDriver(new MockDriver());

        $driver::setKeepStaticConnections(true);

        $params = [
            'driver' => 'pdo_mysql',
            'charset' => 'UTF8',
            'host' => 'foo',
            'dbname' => 'doctrine_test_bundle',
            'user' => 'user',
            'password' => 'password',
            'port' => 3306,
            'dama.keep_static' => true,
            'dama.connection_name' => 'foo',
            'some_closure' => function (): void {},
        ];

        /** @var StaticConnection $connection1 */
        $connection1 = $driver->connect(['dama.connection_name' => 'foo'] + $params);
        /** @var StaticConnection $connection2 */
        $connection2 = $driver->connect(['dama.connection_name' => 'bar'] + $params);

        $this->assertInstanceOf(StaticConnection::class, $connection1);
        $this->assertNotSame($connection1->getWrappedConnection(), $connection2->getWrappedConnection());

        $driver = new StaticDriver(new MockDriver());

        /** @var StaticConnection $connectionNew1 */
        $connectionNew1 = $driver->connect(['dama.connection_name' => 'foo'] + $params);
        /** @var StaticConnection $connectionNew2 */
        $connectionNew2 = $driver->connect(['dama.connection_name' => 'bar'] + $params);

        $this->assertSame($connection1->getWrappedConnection(), $connectionNew1->getWrappedConnection());
        $this->assertSame($connection2->getWrappedConnection(), $connectionNew2->getWrappedConnection());

        /** @var StaticConnection $connection1 */
        $connection1 = $driver->connect($params);
        /** @var StaticConnection $connection2 */
        $connection2 = $driver->connect($params);
        $this->assertSame($connection1->getWrappedConnection(), $connection2->getWrappedConnection());

        /** @var StaticConnection $connection3 */
        $connection3 = $driver->connect(['host' => 'bar'] + $params);
        $this->assertNotSame($connection1->getWrappedConnection(), $connection3->getWrappedConnection());
    }

    public function testConnectWithPlatform(): void
    {
        $driver = new StaticDriver(new MockDriver());

        $driver::setKeepStaticConnections(true);

        $platform = $this->createMock(AbstractPlatform::class);
        $platform
            ->expects(self::exactly(7))
            ->method('supportsSavepoints')
            ->willReturn(true)
        ;

        $params = [
            'driver' => 'pdo_mysql',
            'charset' => 'UTF8',
            'host' => 'foo',
            'dbname' => 'doctrine_test_bundle',
            'user' => 'user',
            'password' => 'password',
            'port' => 3306,
            'platform' => $platform,
            'dama.keep_static' => true,
            'dama.connection_name' => 'foo',
            'some_closure' => function (): void {},
        ];

        /** @var StaticConnection $connection1 */
        $connection1 = $driver->connect(['dama.connection_name' => 'foo'] + $params);
        /** @var StaticConnection $connection2 */
        $connection2 = $driver->connect(['dama.connection_name' => 'bar'] + $params);

        $this->assertInstanceOf(StaticConnection::class, $connection1);
        $this->assertNotSame($connection1->getWrappedConnection(), $connection2->getWrappedConnection());

        $driver = new StaticDriver(new MockDriver());

        /** @var StaticConnection $connectionNew1 */
        $connectionNew1 = $driver->connect(['dama.connection_name' => 'foo'] + $params);
        /** @var StaticConnection $connectionNew2 */
        $connectionNew2 = $driver->connect(['dama.connection_name' => 'bar'] + $params);

        $this->assertSame($connection1->getWrappedConnection(), $connectionNew1->getWrappedConnection());
        $this->assertSame($connection2->getWrappedConnection(), $connectionNew2->getWrappedConnection());

        /** @var StaticConnection $connection1 */
        $connection1 = $driver->connect($params);
        /** @var StaticConnection $connection2 */
        $connection2 = $driver->connect($params);
        $this->assertSame($connection1->getWrappedConnection(), $connection2->getWrappedConnection());

        /** @var StaticConnection $connection3 */
        $connection3 = $driver->connect(['host' => 'bar'] + $params);
        $this->assertNotSame($connection1->getWrappedConnection(), $connection3->getWrappedConnection());
    }
}
