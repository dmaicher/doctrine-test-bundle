<?php

declare(strict_types=1);

namespace Tests\Functional;

use DAMA\DoctrineTestBundle\Doctrine\DBAL\StaticDriver;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;

/**
 * Keeps the changes of a test for the test depending on it with a savepoint level, as documented in the README.
 */
class PhpunitDependsTest extends TestCase
{
    use FunctionalTestTrait;

    public function testChangesAreKept(): void
    {
        $this->insertRow();
        $this->assertRowCount(1);

        StaticDriver::createSavepoint();
    }

    #[Depends('testChangesAreKept')]
    public function testKeptChangesAreVisibleInTheDependentTest(): void
    {
        StaticDriver::rollBackAndReleaseSavepoint();

        $this->assertRowCount(1);
        $this->insertRow();
    }

    #[Depends('testKeptChangesAreVisibleInTheDependentTest')]
    public function testKeptChangesAreRolledBackAfterTheDependentTest(): void
    {
        $this->assertSame(0, StaticDriver::getSavepointLevel());
        $this->assertRowCount(0);
    }
}
