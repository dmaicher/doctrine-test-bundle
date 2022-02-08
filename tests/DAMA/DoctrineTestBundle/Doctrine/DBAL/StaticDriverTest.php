<?php

namespace Tests\DAMA\DoctrineTestBundle\Doctrine\DBAL;

use DAMA\DoctrineTestBundle\Doctrine\DBAL\StaticConnection;
use DAMA\DoctrineTestBundle\Doctrine\DBAL\StaticDriver;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class StaticDriverTest extends TestCase
{
    /**
     * @var AbstractPlatform|MockObject
     */
    private $platform;

    public function setUp(): void
    {
        $this->platform = $this->createMock(AbstractPlatform::class);
        $this->platform->closureForTest = function() {};
    }

    public function testReturnCorrectPlatform(): void
    {
        $driver = new StaticDriver(new MockDriver(), $this->platform);

        $this->assertSame($this->platform, $driver->getDatabasePlatform());
        $this->assertSame($this->platform, $driver->createDatabasePlatformForVersion('1'));
    }

    public function testConnect(): void
    {
        $driver = new StaticDriver(new MockDriver(), $this->platform);

        $driver::setKeepStaticConnections(true);

        $params = [
            'driver' => 'pdo_mysql',
            'charset' => 'UTF8',
            'host' => 'foo',
            'dbname' => 'doctrine_test_bundle',
            'user' => 'user',
            'password' => 'password',
            'port' => null,
            'platform' => $this->platform,
            'dama.keep_static' => true,
            'dama.connection_name' => 'foo',
        ];

        /** @var StaticConnection $connection1 */
        $connection1 = $driver->connect(['dama.connection_name' => 'foo'] + $params, 'username');
        /** @var StaticConnection $connection2 */
        $connection2 = $driver->connect(['dama.connection_name' => 'bar'] + $params, null, 'password');

        $this->assertInstanceOf(StaticConnection::class, $connection1);
        $this->assertNotSame($connection1->getWrappedConnection(), $connection2->getWrappedConnection());

        $driver = new StaticDriver(new MockDriver(), $this->platform);

        /** @var StaticConnection $connectionNew1 */
        $connectionNew1 = $driver->connect(['dama.connection_name' => 'foo'] + $params, 'username');
        /** @var StaticConnection $connectionNew2 */
        $connectionNew2 = $driver->connect(['dama.connection_name' => 'bar'] + $params, null, 'password');

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
