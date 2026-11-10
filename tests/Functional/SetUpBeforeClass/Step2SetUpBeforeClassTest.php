<?php

declare(strict_types=1);

namespace Tests\Functional\SetUpBeforeClass;

use PHPUnit\Framework\TestCase;
use Tests\Functional\app\AppKernel;
use Tests\Functional\FunctionalTestTrait;

/**
 * Runs right after a test without database rollback, see Step1SkipDatabaseRollbackTest.
 */
class Step2SetUpBeforeClassTest extends TestCase
{
    use FunctionalTestTrait;

    public static function setUpBeforeClass(): void
    {
        $kernel = new AppKernel('test', true);
        $kernel->boot();
        $kernel->getContainer()->get('doctrine.dbal.default_connection')->insert('test', ['test' => 'step2']);
        $kernel->shutdown();
    }

    public function testChangesFromSetUpBeforeClassAreRolledBack(): void
    {
        $this->assertSame([0], $this->connection->fetchFirstColumn("SELECT COUNT(*) FROM test WHERE test = 'step2'"));
    }
}
