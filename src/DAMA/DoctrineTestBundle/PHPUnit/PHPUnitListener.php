<?php

namespace DAMA\DoctrineTestBundle\PHPUnit;

use DAMA\DoctrineTestBundle\Doctrine\DBAL\StaticDriver;
use PHPUnit\Framework\BaseTestListener;
use PHPUnit\Framework\Test;
use PHPUnit\Framework\TestSuite;

class PHPUnitListener extends BaseTestListener
{
    /**
     * @param Test $test
     */
    public function startTest(Test $test)
    {
        StaticDriver::beginTransaction();
    }

    /**
     * @param Test $test
     * @param int  $time
     */
    public function endTest(Test $test, $time)
    {
        StaticDriver::rollBack();
    }

    /**
     * @param TestSuite $suite
     */
    public function startTestSuite(TestSuite $suite)
    {
        StaticDriver::setKeepStaticConnections(true);
    }
}
