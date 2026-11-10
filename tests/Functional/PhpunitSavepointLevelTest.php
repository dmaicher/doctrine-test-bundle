<?php

declare(strict_types=1);

namespace Tests\Functional;

use DAMA\DoctrineTestBundle\Doctrine\DBAL\StaticDriver;
use DAMA\DoctrineTestBundle\PHPUnit\PHPUnitExtension;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;
use Tests\Functional\app\AppKernel;

/**
 * Shares rows created in setUpBeforeClass() with all tests of the class, each test still being rolled back.
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

    public static function tearDownAfterClass(): void
    {
        // the shared rows must not leak into the following classes, whatever their order:
        // the next class starts like this, which drops the level, and the rows were not committed
        PHPUnitExtension::restartTransaction(false);
        self::assertSame(0, StaticDriver::getSavepointLevel());

        $kernel = new AppKernel('test', true);
        $kernel->boot();
        self::assertSame([0], $kernel->getContainer()->get('doctrine.dbal.default_connection')->fetchFirstColumn('SELECT COUNT(*) FROM test'));
        $kernel->shutdown();
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
