<?php

namespace Tests\Doctrine\DBAL;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySQL80Platform;
use Doctrine\DBAL\ServerVersionProvider;

if (method_exists(Connection::class, 'getEventManager')) {
    // DBAL < 4
    class MockDriver implements Driver
    {
        use MockDriverTrait;

        public function getDatabasePlatform(): AbstractPlatform
        {
            return new MySQL80Platform();
        }
    }
} else {
    // DBAL >= 4
    class MockDriver implements Driver
    {
        use MockDriverTrait;

        public function getDatabasePlatform(ServerVersionProvider $versionProvider): AbstractPlatform
        {
            return new MySQL80Platform();
        }
    }
}
