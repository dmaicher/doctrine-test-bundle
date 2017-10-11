<?php

namespace DAMA\DoctrineTestBundle\PHPUnit;

use DAMA\DoctrineTestBundle\Doctrine\DBAL\StaticDriver;

/**
 * @deprecated to be removed in 4.0.0. Just use PHPUnitListener instead for PHPUnit 5 and 6+.
 */
class LegacyPHPUnitListener extends \PHPUnit_Framework_BaseTestListener
{
    public function startTest(\PHPUnit_Framework_Test $test)
    {
        StaticDriver::beginTransaction();
    }

    public function endTest(\PHPUnit_Framework_Test $test, $time)
    {
        StaticDriver::rollBack();
    }

    public function startTestSuite(\PHPUnit_Framework_TestSuite $suite)
    {
        StaticDriver::setKeepStaticConnections(true);
    }
}


