<?php

declare(strict_types=1);

namespace Tests\Functional;

use PHPUnit\Framework\Attributes\Depends;

class PhpunitWithExtendingClassAttributeTest extends AbstractTestClassWithSkipAttribute
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
