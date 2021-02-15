<?php

namespace DAMA\DoctrineTestBundle\Doctrine\DBAL;

use PackageVersions\Versions;

$dbalVersion = Versions::getVersion('doctrine/dbal');

if (version_compare($dbalVersion, '2.11.0', '<')) {
    // dbal v2
    class StaticDriver extends AbstractStaticDriverV2
    {
    }
} else {
    // dbal v3
    class StaticDriver extends AbstractStaticDriverV3
    {
    }
}
