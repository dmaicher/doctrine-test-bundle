<?php

declare(strict_types=1);

namespace Tests\Doctrine\DBAL;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\ServerVersionProvider;

if (method_exists(Connection::class, 'getEventManager')) {
    // DBAL < 4
    class MockDriver implements Driver
    {
        use MockDriverTrait;

        public function getDatabasePlatform(): AbstractPlatform
        {
            return new MySQLPlatform();
        }
    }
} else {
    // DBAL >= 4
    class MockDriver implements Driver
    {
        use MockDriverTrait;

        public function getDatabasePlatform(ServerVersionProvider $versionProvider): AbstractPlatform
        {
            return new MySQLPlatform();
        }
    }
}
