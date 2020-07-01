<?php

namespace Tests\Functional;

use DAMA\DoctrineTestBundle\DependencyInjection\DoNotRunInTransactionInterface;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Tests that the opt-out from transactions using DoNotRunInTransactionInterface works.
 */
class NoTransactionFunctionalTest extends TestCase implements DoNotRunInTransactionInterface
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

    public function testNotRunInTransaction(): void
    {
        $this->insertRow();
        $this->assertFalse($this->connection->isTransactionActive());
        $this->assertRowCount(1);
    }

    public function testLastTestsResultsNotDeleted(): void
    {
        $this->assertRowCount(1);
    }
}
