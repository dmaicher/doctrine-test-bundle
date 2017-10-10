<?php

namespace DAMA\DoctrineTestBundle\PHPUnit;

use DAMA\DoctrineTestBundle\Doctrine\DBAL\StaticDriver;

if (!class_exists('\PHPUnit_Framework_BaseTestListener')) {
    // PHPUnit 6+
    class PHPUnitListener extends \PHPUnit\Framework\BaseTestListener
    {
        public function startTest(\PHPUnit\Framework\Test $test)
        {
            StaticDriver::beginTransaction();
        }

        public function endTest(\PHPUnit\Framework\Test $test, $time)
        {
            StaticDriver::rollBack();
        }

        public function startTestSuite(\PHPUnit\Framework\TestSuite $suite)
        {
            StaticDriver::setKeepStaticConnections(true);
        }
    }
} else {
    // PHPUnit 5
    class PHPUnitListener extends \PHPUnit_Framework_BaseTestListener
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
}


