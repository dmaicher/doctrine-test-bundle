<?php

namespace DAMA\DoctrineTestBundle\PHPUnit;

use DAMA\DoctrineTestBundle\Doctrine\DBAL\StaticDriver;

if (class_exists('\PHPUnit\Framework\BaseTestListener')) {
    // PHPUnit 6
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
} elseif (trait_exists('\PHPUnit\Framework\TestListenerDefaultImplementation')) {
    // PHPUnit 7+
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
            StaticDriver::setKeepStaticConnections(true);
        }
    }
}
