<?php

declare(strict_types=1);

namespace Tests\Functional;

use DAMA\DoctrineTestBundle\PHPUnit\KeepDatabaseChanges;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;

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

    #[Depends('testKeptChangesAreVisible')]
    #[KeepDatabaseChanges]
    public function testChangesOfATestThatDidNotPassAreNotKept(): void
    {
        $this->assertRowCount(1);
        $this->insertRow();

        $this->markTestIncomplete();
    }

    #[Depends('testKeptChangesAreVisible')]
    public function testOnlyKeptChangesAreVisible(): void
    {
        $this->assertRowCount(1);
    }
}
