<?php

namespace DAMA\DoctrineTestBundle\Doctrine\DBAL;

use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Driver\ServerInfoAwareConnection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\VersionAwarePlatformDriver;

/**
 * @internal
 */
final class VersionAwarePlatformStaticDriver extends StaticDriver implements VersionAwarePlatformDriver
{
    public function createDatabasePlatformForVersion($version): AbstractPlatform
    {
        if (!$this->underlyingDriver instanceof VersionAwarePlatformDriver) {
            throw new \LogicException('Underlying driver is not a VersionAwarePlatformDriver');
        }

        return $this->underlyingDriver->createDatabasePlatformForVersion($version);
    }

    protected function getDatabasePlatformForVersion(Connection $driverConnection, ?string $version): AbstractPlatform
    {
        if ($version === null && $driverConnection instanceof ServerInfoAwareConnection) {
            $version = $driverConnection->getServerVersion();
        }

        return $version !== null ? $this->createDatabasePlatformForVersion($version) : $this->getDatabasePlatform();
    }
}
