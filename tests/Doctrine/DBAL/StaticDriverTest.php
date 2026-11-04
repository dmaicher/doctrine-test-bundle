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

        self::resetStaticDriver();

        $this->driver = new MockDriver(
            $this->createStub(Connection::class),
            $this->createStub(AbstractSchemaManager::class),
            $this->createStub(ExceptionConverter::class),
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        self::resetStaticDriver();
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

    public function testSavepointLevels(): void
    {
        $driver = new StaticDriver($this->driver);
        $driver::setKeepStaticConnections(true);

        $driver->connect(['dama.connection_key' => 'foo']);
        StaticDriver::createSavepoint();
        StaticDriver::createSavepoint();
        $this->assertSame(2, StaticDriver::getSavepointLevel());

        // a connection opened afterwards gets the existing levels
        $driver->connect(['dama.connection_key' => 'bar']);
        $this->assertSame(['SAVEPOINT DAMA_LEVEL_1', 'SAVEPOINT DAMA_LEVEL_2', 'SAVEPOINT DAMA_LEVEL_1', 'SAVEPOINT DAMA_LEVEL_2'], $this->driver->executedSql);

        $this->driver->executedSql = [];
        StaticDriver::rollBackToSavepoint();
        $this->assertSame(['ROLLBACK TO SAVEPOINT DAMA_LEVEL_2', 'ROLLBACK TO SAVEPOINT DAMA_LEVEL_2'], $this->driver->executedSql);

        $this->driver->executedSql = [];
        StaticDriver::releaseSavepoint();
        $this->assertSame(['ROLLBACK TO SAVEPOINT DAMA_LEVEL_2', 'ROLLBACK TO SAVEPOINT DAMA_LEVEL_2', 'RELEASE SAVEPOINT DAMA_LEVEL_2', 'RELEASE SAVEPOINT DAMA_LEVEL_2'], $this->driver->executedSql);
        $this->assertSame(1, StaticDriver::getSavepointLevel());

        StaticDriver::rollBack();
        $this->assertSame(0, StaticDriver::getSavepointLevel());
    }

    public function testRollBackToSavepointWithoutLevel(): void
    {
        $this->expectException(\LogicException::class);

        StaticDriver::rollBackToSavepoint();
    }

    public function testReleaseSavepointWithoutLevel(): void
    {
        $this->expectException(\LogicException::class);

        StaticDriver::releaseSavepoint();
    }

    /**
     * The static state is shared with the functional tests running in the same process: start from and leave a blank one.
     * A static connection opened by a previous test is just dropped, the next test opens a new one.
     */
    private static function resetStaticDriver(): void
    {
        $class = new \ReflectionClass(StaticDriver::class);

        foreach (['connections' => [], 'platforms' => [], 'openTransactions' => [], 'savepointLevel' => 0] as $property => $value) {
            $class->getProperty($property)->setValue(null, $value);
        }
    }
}
