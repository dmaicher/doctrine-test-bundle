<?php

declare(strict_types=1);

namespace Tests\Functional;

use DAMA\DoctrineTestBundle\PHPUnit\ShareSetUpBeforeClassChanges;
use PHPUnit\Framework\Attributes\BeforeClass;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;
use Tests\Functional\app\AppKernel;

#[ShareSetUpBeforeClassChanges]
class PhpunitShareSetUpBeforeClassChangesTest extends TestCase
{
    use FunctionalTestTrait;

    // with the same priority, PHPUnit runs #[BeforeClass] methods before setUpBeforeClass()
    #[BeforeClass]
    public static function insertRowBeforeSetUpBeforeClass(): void
    {
        self::insertSharedRow('before');
    }

    public static function setUpBeforeClass(): void
    {
        self::insertSharedRow('shared');
    }

    #[BeforeClass(-1)]
    public static function insertRowAfterSetUpBeforeClass(): void
    {
        self::insertSharedRow('after');
    }

    public function testChangesFromBeforeClassMethodsAreVisible(): void
    {
        $this->assertSame(['after', 'before', 'shared'], $this->connection->fetchFirstColumn('SELECT test FROM test ORDER BY test'));
        $this->insertRow();
        $this->assertRowCount(4);
    }

    #[Depends('testChangesFromBeforeClassMethodsAreVisible')]
    public function testChangesFromPreviousTestAreRolledBack(): void
    {
        $this->assertRowCount(3);
    }

    private static function insertSharedRow(string $value): void
    {
        $kernel = new AppKernel('test', true);
        $kernel->boot();
        $kernel->getContainer()->get('doctrine.dbal.default_connection')->insert('test', ['test' => $value]);
        $kernel->shutdown();
    }
}
