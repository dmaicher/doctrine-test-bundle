<?php

namespace DAMA\DoctrineTestBundle\PHPUnit;

use DAMA\DoctrineTestBundle\DependencyInjection\DoNotRunInTransactionInterface;
use DAMA\DoctrineTestBundle\Doctrine\DBAL\StaticDriver;

class PHPUnitListener implements \PHPUnit\Framework\TestListener
{
    use \PHPUnit\Framework\TestListenerDefaultImplementation;

    public function startTest(\PHPUnit\Framework\Test $test): void
    {
        StaticDriver::beginTransaction();
    }

    public function endTest(\PHPUnit\Framework\Test $test, float $time): void
    {
        StaticDriver::rollBack();
    }

    public function startTestSuite(\PHPUnit\Framework\TestSuite $suite): void
    {
        $keepStatic = !is_subclass_of($suite->getName(), DoNotRunInTransactionInterface::class);
        StaticDriver::setKeepStaticConnections($keepStatic);
    }
}
