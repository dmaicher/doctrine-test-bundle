<?php

namespace DAMA\DoctrineTestBundle\Doctrine\DBAL;

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
}
