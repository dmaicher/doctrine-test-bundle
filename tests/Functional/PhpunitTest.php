<?php

namespace Tests\Functional;

use DAMA\DoctrineTestBundle\Doctrine\DBAL\StaticDriver;
use Doctrine\DBAL\Exception\TableNotFoundException;
use PHPUnit\Event\Test\BeforeTestMethodErroredSubscriber;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;

class PhpunitTest extends TestCase
{
    use FunctionalTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->init();
        /** @phpstan-ignore method.notFound,function.alreadyNarrowedType */
        if ((method_exists($this, 'name') ? $this->name() : $this->getName()) === 'testSkippedTestDuringSetup') {
            $this->markTestSkipped();
        }

        if (interface_exists(BeforeTestMethodErroredSubscriber::class)
            /** @phpstan-ignore method.notFound,function.alreadyNarrowedType */
            && (method_exists($this, 'name') ? $this->name() : $this->getName()) === 'testIncompleteTestDuringSetup'
        ) {
            $this->markTestIncomplete();
        }
    }

    public function testChangeDbState(): void
    {
        $this->assertRowCount(0);
        $this->insertRow();
        $this->assertRowCount(1);
    }

    #[Depends('testChangeDbState')]
    public function testPreviousChangesAreRolledBack(): void
    {
        $this->assertRowCount(0);
    }

    #[DataProvider('someDataProvider')]
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

        // this will make sure the next select uses the read replica
        $this->connection->close();
        $this->assertRowCount(1);
    }

    #[Depends('testChangeDbStateForReplicaConnection')]
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

    #[Depends('testChangeDbStateWithinTransaction')]
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

    #[Depends('testChangeDbStateWithSavePoint')]
    public function testPreviousChangesAreRolledBackAfterUsingSavePoint(): void
    {
        $this->assertRowCount(0);
    }

    public function testSkippedTest(): void
    {
        $this->markTestSkipped();
    }

    public function testSkippedTestDuringSetup(): void
    {
        $this->expectNotToPerformAssertions();
    }

    public function testIncompleteTestDuringSetup(): void
    {
        $this->expectNotToPerformAssertions();
    }

    public function testMarkIncomplete(): void
    {
        $this->markTestIncomplete();
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

    #[Depends('testTransactionalBehaviorCanBeDisabledDuringRuntime')]
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
