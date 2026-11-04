<?php

declare(strict_types=1);

namespace Tests\Functional;

use DAMA\DoctrineTestBundle\Doctrine\DBAL\StaticDriver;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;
use Tests\Functional\app\AppKernel;

/**
 * Shares rows created in setUpBeforeClass() with all tests of the class, each test still being rolled back.
 * Runs right before PhpunitTest, whose first test makes sure nothing leaks into the following classes.
 */
class PhpunitSavepointLevelTest extends TestCase
{
    use FunctionalTestTrait;

    public static function setUpBeforeClass(): void
    {
        $kernel = new AppKernel('test', true);
        $kernel->boot();
        $kernel->getContainer()->get('doctrine.dbal.default_connection')->insert('test', ['test' => 'shared']);
        $kernel->shutdown();

        StaticDriver::createSavepoint();
    }

    public function testRowsFromSetUpBeforeClassAreVisible(): void
    {
        $this->assertRowCount(1);
        $this->insertRow();
        $this->assertRowCount(2);
    }

    #[Depends('testRowsFromSetUpBeforeClassAreVisible')]
    public function testChangesAreRolledBackToTheSavepointLevel(): void
    {
        $this->assertRowCount(1);
    }

    public function testConnectionOpenedAfterTheSavepointLevelWasCreated(): void
    {
        $secondary = $this->kernel->getContainer()->get('doctrine.dbal.secondary_connection');
        $secondary->insert('secondary_test', ['test' => 'foo']);

        $this->assertSame([1], $secondary->fetchFirstColumn('SELECT COUNT(*) FROM secondary_test'));
    }

    #[Depends('testConnectionOpenedAfterTheSavepointLevelWasCreated')]
    public function testConnectionOpenedAfterTheSavepointLevelWasCreatedIsRolledBack(): void
    {
        $secondary = $this->kernel->getContainer()->get('doctrine.dbal.secondary_connection');

        $this->assertSame([0], $secondary->fetchFirstColumn('SELECT COUNT(*) FROM secondary_test'));
    }

    public function testApplicationTransactionsStillWork(): void
    {
        $this->beginTransaction();
        $this->insertRow();
        $this->rollbackTransaction();
        $this->assertRowCount(1);

        $this->beginTransaction();
        $this->insertRow();
        $this->commitTransaction();
        $this->assertRowCount(2);
    }

    #[Depends('testApplicationTransactionsStillWork')]
    public function testCommittedApplicationTransactionIsRolledBackToTheSavepointLevel(): void
    {
        $this->assertRowCount(1);
    }
}
