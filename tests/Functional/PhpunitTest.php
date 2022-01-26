<?php

namespace Tests\Functional;

use Doctrine\DBAL\Exception\TableNotFoundException;
use PHPUnit\Framework\TestCase;

class PhpunitTest extends TestCase
{
    use FunctionalTestTrait;

    public function testChangeDbState(): void
    {
        $this->assertRowCount(0);
        $this->insertRow();
        $this->assertRowCount(1);
    }

    /**
     * @depends testChangeDbState
     */
    public function testPreviousChangesAreRolledBack(): void
    {
        $this->assertRowCount(0);
    }

    public function testChangeDbStateWithinTransaction(): void
    {
        $this->assertRowCount(0);

        $this->beginTransaction();
        $this->insertRow();
        $this->assertRowCount(1);
        $this->rollbackTransaction();
        $this->assertRowCount(0);

        $this->beginTransaction();
        $this->insertRow();
        $this->commitTransaction();
        $this->assertRowCount(1);
    }

    /**
     * @depends testChangeDbStateWithinTransaction
     */
    public function testPreviousChangesAreRolledBackAfterTransaction(): void
    {
        $this->assertRowCount(0);
    }

    public function testChangeDbStateWithSavePoint(): void
    {
        $this->assertRowCount(0);
        $this->createSavepoint('foo');
        $this->insertRow();
        $this->assertRowCount(1);
        $this->rollbackSavepoint('foo');
        $this->assertRowCount(0);
        $this->insertRow();
    }

    /**
     * @depends testChangeDbStateWithSavePoint
     */
    public function testPreviousChangesAreRolledBackAfterUsingSavePoint(): void
    {
        $this->assertRowCount(0);
    }

    public function testRollBackChangesWithReOpenedConnection(): void
    {
        $this->connection->close();
        $this->connection->beginTransaction();
        $this->connection->commit();
        $this->assertRowCount(0);
    }

    public function testWillThrowSpecificException(): void
    {
        $this->expectException(TableNotFoundException::class);
        $this->connection->insert('does_not_exist', ['foo' => 'bar']);
    }
}
