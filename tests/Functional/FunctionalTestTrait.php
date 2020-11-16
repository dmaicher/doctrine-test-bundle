<?php

namespace Tests\Functional;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpKernel\KernelInterface;
use Tests\Functional\app\AppKernel;

trait FunctionalTestTrait
{
    /**
     * @var KernelInterface
     */
    private $kernel;

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @BeforeScenario
     */
    public function setUp(): void
    {
        $this->kernel = new AppKernel('test', true);
        $this->kernel->boot();
        $this->connection = $this->kernel->getContainer()->get('doctrine.dbal.default_connection');
    }

    /**
     * @AfterScenario
     */
    public function tearDown(): void
    {
        $this->kernel->shutdown();
    }

    /**
     * @Then there are :count rows
     * @Then there is :count row
     */
    public function assertRowCount($count): void
    {
        if (method_exists($this->connection, 'fetchFirstColumn')) {
            // dbal v3
            Assert::assertEquals([$count], $this->connection->fetchFirstColumn('SELECT COUNT(*) FROM test'));
        } else {
            // dbal v2
            Assert::assertEquals($count, $this->connection->fetchColumn('SELECT COUNT(*) FROM test'));
        }
    }

    /**
     * @When I insert a new row
     */
    public function insertRow(): void
    {
        $this->connection->insert('test', [
            'test' => 'foo',
        ]);
    }

    /**
     * @When I begin a transaction
     */
    public function beginTransaction(): void
    {
        $this->connection->beginTransaction();
    }

    /**
     * @When I rollback the transaction
     */
    public function rollbackTransaction(): void
    {
        $this->connection->rollBack();
    }

    /**
     * @When I commit the transaction
     */
    public function commitTransaction(): void
    {
        $this->connection->commit();
    }

    /**
     * @When I create a savepoint named :name
     */
    public function createSavepoint(string $name): void
    {
        $this->connection->createSavepoint($name);
    }

    /**
     * @When I rollback the savepoint named :name
     */
    public function rollbackSavepoint(string $name): void
    {
        $this->connection->rollbackSavepoint($name);
    }
}
