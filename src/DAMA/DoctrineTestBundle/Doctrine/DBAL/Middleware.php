<?php

namespace DAMA\DoctrineTestBundle\Doctrine\DBAL;

use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Middleware as MiddlewareInterface;
use Doctrine\DBAL\VersionAwarePlatformDriver;

final class Middleware implements MiddlewareInterface
{
    public function wrap(Driver $driver): Driver
    {
        if ($driver instanceof VersionAwarePlatformDriver) {
            return new VersionAwarePlatformStaticDriver($driver);
        }

        return new StaticDriver($driver);
    }
}
