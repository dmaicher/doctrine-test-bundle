<?php

declare(strict_types=1);

namespace Tests\Functional\SetUpBeforeClass;

use DAMA\DoctrineTestBundle\PHPUnit\SkipDatabaseRollback;
use PHPUnit\Framework\TestCase;

/**
 * Runs right before Step2SetUpBeforeClassTest, as test files run in alphabetical order:
 * its setUpBeforeClass() follows a test without database rollback.
 */
#[SkipDatabaseRollback]
class Step1SkipDatabaseRollbackTest extends TestCase
{
    public function testWithoutDatabaseRollback(): void
    {
        $this->expectNotToPerformAssertions();
    }
}
