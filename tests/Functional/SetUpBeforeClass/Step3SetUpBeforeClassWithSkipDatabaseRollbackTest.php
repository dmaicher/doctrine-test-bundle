<?php

declare(strict_types=1);

namespace Tests\Functional\SetUpBeforeClass;

use DAMA\DoctrineTestBundle\PHPUnit\SkipDatabaseRollback;
use PHPUnit\Framework\TestCase;
use Tests\Functional\app\AppKernel;
use Tests\Functional\FunctionalTestTrait;

/**
 * Runs right after a test with database rollback, see Step2SetUpBeforeClassTest.
 */
#[SkipDatabaseRollback]
class Step3SetUpBeforeClassWithSkipDatabaseRollbackTest extends TestCase
{
    use FunctionalTestTrait;

    public static function setUpBeforeClass(): void
    {
        $kernel = new AppKernel('test', true);
        $kernel->boot();
        $kernel->getContainer()->get('doctrine.dbal.default_connection')->insert('test', ['test' => 'step3']);
        $kernel->shutdown();
    }

    public function testChangesFromSetUpBeforeClassAreCommitted(): void
    {
        $this->assertSame([1], $this->connection->fetchFirstColumn("SELECT COUNT(*) FROM test WHERE test = 'step3'"));

        // cleanup persisted row to not affect any other tests afterwards
        $this->connection->executeQuery('DELETE FROM test WHERE test = \'step3\'');
    }
}
