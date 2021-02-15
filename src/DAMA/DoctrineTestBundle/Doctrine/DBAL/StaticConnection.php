<?php

namespace DAMA\DoctrineTestBundle\Doctrine\DBAL;

use PackageVersions\Versions;

$dbalVersion = Versions::getVersion('doctrine/dbal');

if (version_compare($dbalVersion, '2.11.0', '<')) {
    // dbal v2
    /**
     * Wraps a real connection and just skips the first call to beginTransaction as a transaction is already started on the underlying connection.
     */
    class StaticConnection extends AbstractStaticConnectionV2
    {
    }
} else {
    // dbal v3
    /**
     * Wraps a real connection and just skips the first call to beginTransaction as a transaction is already started on the underlying connection.
     */
    class StaticConnection extends AbstractStaticConnectionV3
    {
    }
}
