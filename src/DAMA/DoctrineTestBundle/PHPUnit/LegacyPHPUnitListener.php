<?php

namespace DAMA\DoctrineTestBundle\PHPUnit;

use DAMA\DoctrineTestBundle\Doctrine\DBAL\StaticDriver;

class LegacyPHPUnitListener extends \PHPUnit_Framework_BaseTestListener
{
    /**
     * @param \PHPUnit_Framework_Test $test
     */
    public function startTest(\PHPUnit_Framework_Test $test)
    {
        StaticDriver::beginTransaction();
    }

    /**
     * @param \PHPUnit_Framework_Test $test
     * @param $time
     */
    public function endTest(\PHPUnit_Framework_Test $test, $time)
    {
        StaticDriver::rollBack();
    }

    /**
     * @param \PHPUnit_Framework_TestSuite $suite
     */
    public function startTestSuite(\PHPUnit_Framework_TestSuite $suite)
    {
        StaticDriver::setKeepStaticConnections(true);
    }
}
