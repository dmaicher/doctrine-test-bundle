<?php

namespace Tests\DAMA\DoctrineTestBundle\Doctrine\DBAL;

use DAMA\DoctrineTestBundle\Doctrine\DBAL\StaticConnection;
use DAMA\DoctrineTestBundle\Doctrine\DBAL\StaticDriver;

class StaticDriverTest extends \PHPUnit_Framework_TestCase
{
    public function testConnect()
    {
        $driver = new StaticDriver(new MockDriver());

        $driver::setKeepStaticConnections(true);

        $connection1 = $driver->connect(['database_name' => 1], 'user1', 'pw1');
        $connection2 = $driver->connect(['database_name' => 2], 'user1', 'pw2');

        $this->assertInstanceOf(StaticConnection::class, $connection1);
        $this->assertNotSame($connection1->getWrappedConnection(), $connection2->getWrappedConnection());

        $driver = new StaticDriver(new MockDriver());

        $connectionNew1 = $driver->connect(['database_name' => 1], 'user1', 'pw1');
        $connectionNew2 = $driver->connect(['database_name' => 2], 'user1', 'pw2');

        $this->assertSame($connection1->getWrappedConnection(), $connectionNew1->getWrappedConnection());
        $this->assertSame($connection2->getWrappedConnection(), $connectionNew2->getWrappedConnection());
    }
}
