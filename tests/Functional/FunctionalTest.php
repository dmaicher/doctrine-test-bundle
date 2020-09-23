<?php

namespace Tests\Functional;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\TableNotFoundException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\KernelInterface;

class FunctionalTest extends TestCase
{
    /**
     * @var KernelInterface
     */
    private $kernel;

    /**
     * @var Connection
     */
    private $connection;

    protected function setUp(): void
    {
        $this->kernel = new AppKernel('test', true);
        $this->kernel->boot();
        $this->connection = $this->kernel->getContainer()->get('doctrine.dbal.default_connection');
    }

    protected function tearDown(): void
    {
        $this->kernel->shutdown();
    }

    private function assertRowCount($count): void
    {
        $this->assertEquals($count, $this->connection->fetchColumn('SELECT COUNT(*) FROM test'));
    }

    private function insertRow(): void
    {
        $this->connection->insert('test', [
            'test' => 'foo',
        ]);
    }

    public function testChangeDbState(): void
    {
        $this->assertRowCount(0);
        $this->insertRow();
        $this->assertRowCount(1);
    }

    public function testPreviousChangesAreRolledBack(): void
    {
        $this->assertRowCount(0);
    }

    public function testChangeDbStateWithinTransaction(): void
    {
        $this->assertRowCount(0);

        $this->connection->beginTransaction();
        $this->insertRow();
        $this->assertRowCount(1);
        $this->connection->rollBack();
        $this->assertRowCount(0);

        $this->connection->beginTransaction();
        $this->insertRow();
        $this->connection->commit();
        $this->assertRowCount(1);
    }

    public function testPreviousChangesAreRolledBackAfterTransaction(): void
    {
        $this->assertRowCount(0);
    }

    public function testChangeDbStateWithSavePoint(): void
    {
        $this->assertRowCount(0);
        $this->connection->createSavepoint('foo');
        $this->insertRow();
        $this->assertRowCount(1);
        $this->connection->rollbackSavepoint('foo');
        $this->assertRowCount(0);
        $this->insertRow();
    }

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
}
