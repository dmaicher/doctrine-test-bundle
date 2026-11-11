<?php

declare(strict_types=1);

namespace Tests\Functional;

use DAMA\DoctrineTestBundle\PHPUnit\ShareSetUpBeforeClassChanges;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;
use Tests\Functional\app\AppKernel;

#[ShareSetUpBeforeClassChanges]
class PhpunitShareSetUpBeforeClassChangesTest extends TestCase
{
    use FunctionalTestTrait;

    public static function setUpBeforeClass(): void
    {
        $kernel = new AppKernel('test', true);
        $kernel->boot();
        $kernel->getContainer()->get('doctrine.dbal.default_connection')->insert('test', ['test' => 'shared']);
        $kernel->shutdown();
    }

    public function testChangesFromSetUpBeforeClassAreVisible(): void
    {
        $this->assertRowCount(1);
        $this->insertRow();
        $this->assertRowCount(2);
    }

    #[Depends('testChangesFromSetUpBeforeClassAreVisible')]
    public function testChangesFromPreviousTestAreRolledBack(): void
    {
        $this->assertRowCount(1);
    }
}
