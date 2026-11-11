<?php

declare(strict_types=1);

namespace Tests\Functional;

use DAMA\DoctrineTestBundle\PHPUnit\KeepDatabaseChanges;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;

/**
 * The tests run in the order they are declared.
 */
class PhpunitKeepDatabaseChangesTest extends TestCase
{
    use FunctionalTestTrait;

    #[KeepDatabaseChanges]
    public function testChangesAreKept(): void
    {
        $this->insertRow();
        $this->assertRowCount(1);
    }

    #[Depends('testChangesAreKept')]
    public function testKeptChangesAreVisible(): void
    {
        $this->assertRowCount(1);
        $this->insertRow();
    }

    public function testKeptChangesAreVisibleUntilTheLastDependentTest(): void
    {
        $this->assertRowCount(1);
    }

    #[Depends('testKeptChangesAreVisible')]
    #[KeepDatabaseChanges]
    public function testKeptChangesAreVisibleForIndirectlyDependentTest(): void
    {
        $this->assertRowCount(1);
        $this->insertRow();
    }

    #[Depends('testKeptChangesAreVisibleForIndirectlyDependentTest')]
    public function testChangesKeptByTwoTestsAreVisible(): void
    {
        $this->assertRowCount(2);
    }

    #[KeepDatabaseChanges]
    public function testKeptChangesAreRolledBackAfterTheLastDependentTest(): void
    {
        $this->assertRowCount(0);
        $this->insertRow();
    }

    public function testChangesAreNotKeptWithoutDependentTest(): void
    {
        $this->assertRowCount(0);
    }
}
