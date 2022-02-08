<?php

namespace DAMA\DoctrineTestBundle\Doctrine\DBAL;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\API\ExceptionConverter;
use Doctrine\DBAL\Driver\Connection as DriverConnection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;

/**
 * @internal
 */
abstract class AbstractStaticDriverV3 extends AbstractStaticDriver
{
    public function connect(array $params): DriverConnection
    {
        if (!self::$keepStaticConnections) {
            return $this->underlyingDriver->connect($params);
        }

        $key = sha1(json_encode($params));

        if (!isset(self::$connections[$key])) {
            self::$connections[$key] = $this->underlyingDriver->connect($params);
            self::$connections[$key]->beginTransaction();
        }

        return new StaticConnection(self::$connections[$key]);
    }

    public function getSchemaManager(Connection $conn, AbstractPlatform $platform): AbstractSchemaManager
    {
        return $this->underlyingDriver->getSchemaManager($conn, $platform);
    }

    public function getExceptionConverter(): ExceptionConverter
    {
        return $this->underlyingDriver->getExceptionConverter();
    }
}
