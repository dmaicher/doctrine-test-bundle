<?php

declare(strict_types=1);

namespace Functional;

use DAMA\DoctrineTestBundle\PHPUnit\SkipDatabaseRollback;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;
use Tests\Functional\FunctionalTestTrait;

#[SkipDatabaseRollback]
class PhpunitWithClassAttributeTest extends TestCase
{
    use FunctionalTestTrait;

    public function testTransactionalBehaviorDisabledWithAttributeOnClassLevel(): void
    {
        $this->insertRow();
        $this->assertRowCount(1);
    }

    #[Depends('testTransactionalBehaviorDisabledWithAttributeOnClassLevel')]
    public function testChangesFromPreviousTestAreVisibleWhenDisabledWithAttributeOnClassLevel(): void
    {
        $this->assertRowCount(1);

        // cleanup persisted row to not affect any other tests afterwards
        $this->connection->executeQuery('DELETE FROM test');
    }
}
