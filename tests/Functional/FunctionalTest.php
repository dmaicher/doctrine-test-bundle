<?php

namespace Tests\Functional;

use Doctrine\DBAL\Connection;
use Symfony\Component\HttpKernel\KernelInterface;
use PHPUnit\Framework\TestCase;

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

    private function assertRowCount($count)
    {
        $this->assertEquals($count, $this->connection->fetchColumn('SELECT COUNT(*) FROM test'));
    }

    private function insertRow()
    {
        $this->connection->insert('test', [
            'test' => 'foo',
        ]);
    }

    public function testChangeDbState()
    {
        $this->assertRowCount(0);
        $this->insertRow();
        $this->assertRowCount(1);
    }

    public function testPreviousChangesAreRolledBack()
    {
        $this->assertRowCount(0);
    }

    public function testChangeDbStateWithinTransaction()
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

    public function testPreviousChangesAreRolledBackAfterTransaction()
    {
        $this->assertRowCount(0);
    }

    public function testChangeDbStateWithSavePoint()
    {
        $this->assertRowCount(0);
        $this->connection->createSavepoint('foo');
        $this->insertRow();
        $this->assertRowCount(1);
        $this->connection->rollbackSavepoint('foo');
        $this->assertRowCount(0);
        $this->insertRow();
    }

    public function testPreviousChangesAreRolledBackAfterUsingSavePoint()
    {
        $this->assertRowCount(0);
    }
}
