<?php

namespace DAMA\DoctrineTestBundle\PHPUnit;

use DAMA\DoctrineTestBundle\Doctrine\DBAL\StaticDriver;
use PHPUnit\Runner\AfterLastTestHook;
use PHPUnit\Runner\AfterTestHook;
use PHPUnit\Runner\BeforeFirstTestHook;
use PHPUnit\Runner\BeforeTestHook;

class PHPUnitExtension implements BeforeFirstTestHook, AfterLastTestHook, BeforeTestHook, AfterTestHook
{
    public function executeBeforeFirstTest(): void
    {
        StaticDriver::setKeepStaticConnections(true);
    }

    public function executeBeforeTest(string $test): void
    {
        StaticDriver::beginTransaction();
    }

    public function executeAfterTest(string $test, float $time): void
    {
        StaticDriver::rollBack();
    }

    public function executeAfterLastTest(): void
    {
        StaticDriver::setKeepStaticConnections(false);
    }
}
