<?php

declare(strict_types=1);

namespace Tests\Functional;

use DAMA\DoctrineTestBundle\Doctrine\DBAL\StaticDriver;
use DAMA\DoctrineTestBundle\PHPUnit\KeepDatabaseChanges;
use DAMA\DoctrineTestBundle\PHPUnit\SkipDatabaseRollback;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;

/**
 * No savepoint level can be created without static connections: creating one would make the test runner warn.
 */
#[SkipDatabaseRollback]
class PhpunitKeepDatabaseChangesWithSkipDatabaseRollbackTest extends TestCase
{
    use FunctionalTestTrait;

    #[KeepDatabaseChanges]
    public function testChangesAreCommitted(): void
    {
        $this->insertRow();
        $this->assertRowCount(1);
    }

    #[Depends('testChangesAreCommitted')]
    public function testCommittedChangesAreVisible(): void
    {
        $this->assertSame(0, StaticDriver::getSavepointLevel());
        $this->assertRowCount(1);

        // cleanup persisted row to not affect any other tests afterwards
        $this->connection->executeQuery('DELETE FROM test');
    }
}
