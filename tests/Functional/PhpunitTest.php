<?php

namespace Tests\Functional;

use DAMA\DoctrineTestBundle\Doctrine\DBAL\StaticDriver;
use Doctrine\DBAL\Exception\TableNotFoundException;
use PHPUnit\Framework\TestCase;

class PhpunitTest extends TestCase
{
    use FunctionalTestTrait;

    public function testChangeDbState(): void
    {
        $this->assertRowCount(0);
        $this->insertRow();
        $this->assertRowCount(1);
    }

    /**
     * @depends testChangeDbState
     */
    public function testPreviousChangesAreRolledBack(): void
    {
        $this->assertRowCount(0);
    }

    /**
     * @dataProvider someDataProvider
     */
    public function testWithDataProvider(int $expectedRowCount): void
    {
        $this->assertRowCount($expectedRowCount);
        $this->insertRow();
    }

    /**
     * @return iterable<array{int}>
     */
    public static function someDataProvider(): iterable
    {
        yield [0];
    }

    public function testChangeDbStateForReplicaConnection(): void
    {
        $this->connection = $this->kernel->getContainer()->get('doctrine.dbal.replica_connection');
        $this->assertRowCount(0);
        $this->insertRow();
        $this->assertRowCount(1);
    }

    /**
     * @depends testChangeDbStateForReplicaConnection
     */
    public function testChangeDbStateForReplicaConnectionRolledBack(): void
    {
        $this->connection = $this->kernel->getContainer()->get('doctrine.dbal.replica_connection');
        $this->assertRowCount(0);
        $this->assertFalse($this->connection->isTransactionActive());
    }

    public function testChangeDbStateWithMultipleConnections(): void
    {
        $this->assertRowCount(0);
        $this->insertRow();
        $this->assertRowCount(1);
        $this->connection->close();
        $this->assertRowCount(1);
    }

    public function testChangeDbStateWithinTransaction(): void
    {
        $this->assertRowCount(0);

        $this->beginTransaction();
        $this->insertRow();
        $this->assertRowCount(1);
        $this->rollbackTransaction();
        $this->assertRowCount(0);

        $this->beginTransaction();
        $this->insertRow();
        $this->assertRowCount(1);
        $this->rollbackTransaction();
        $this->assertRowCount(0);

        $this->beginTransaction();
        $this->insertRow();
        $this->commitTransaction();
        $this->assertRowCount(1);

        $this->beginTransaction();
        $this->insertRow();
        $this->commitTransaction();
        $this->assertRowCount(2);
    }

    /**
     * @depends testChangeDbStateWithinTransaction
     */
    public function testPreviousChangesAreRolledBackAfterTransaction(): void
    {
        $this->assertRowCount(0);
    }

    public function testChangeDbStateWithSavePoint(): void
    {
        $this->assertRowCount(0);
        $this->createSavepoint('foo');
        $this->insertRow();
        $this->assertRowCount(1);
        $this->rollbackSavepoint('foo');
        $this->assertRowCount(0);
        $this->insertRow();
    }

    /**
     * @depends testChangeDbStateWithSavePoint
     */
    public function testPreviousChangesAreRolledBackAfterUsingSavePoint(): void
    {
        $this->assertRowCount(0);
    }

    public function testRollBackChangesWithReOpenedConnection(): void
    {
        $this->connection->close();
        $this->connection->beginTransaction();
        $this->connection->commit();
        $this->assertRowCount(0);
    }

    public function testWillThrowSpecificException(): void
    {
        $this->expectException(TableNotFoundException::class);
        $this->connection->insert('does_not_exist', ['foo' => 'bar']);
    }

    public function testTransactionalBehaviorCanBeDisabledDuringRuntime(): void
    {
        StaticDriver::setKeepStaticConnections(false);

        $this->kernel->shutdown();
        $this->init();

        $this->insertRow();
        $this->assertRowCount(1);

        StaticDriver::setKeepStaticConnections(true);
    }

    /**
     * @depends testTransactionalBehaviorCanBeDisabledDuringRuntime
     */
    public function testChangesFromPreviousTestAreVisibleWhenDisabledDuringRuntime(): void
    {
        StaticDriver::setKeepStaticConnections(false);

        $this->kernel->shutdown();
        $this->setUp();

        $this->assertRowCount(1);

        // cleanup persisted row to not affect any other tests afterwards
        $this->connection->executeQuery('DELETE FROM test');

        $this->assertRowCount(0);

        StaticDriver::setKeepStaticConnections(true);
    }
}
