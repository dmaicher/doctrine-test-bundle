<?php

namespace DAMA\DoctrineTestBundle\Doctrine\DBAL;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\API\ExceptionConverter;
use Doctrine\DBAL\Platforms\AbstractPlatform;

/**
 * @internal
 */
abstract class AbstractStaticDriverV3 extends AbstractStaticDriver
{
    /**
     * {@inheritdoc}
     */
    public function connect(array $params)
    {
        if (!self::$keepStaticConnections) {
            return $this->underlyingDriver->connect($params);
        }

        $key = $params['dama.connection_name'] ?? sha1(serialize($params));

        if (!isset(self::$connections[$key])) {
            self::$connections[$key] = $this->underlyingDriver->connect($params);
            self::$connections[$key]->beginTransaction();
        }

        return new StaticConnection(self::$connections[$key]);
    }

    public function getSchemaManager(Connection $conn, AbstractPlatform $platform)
    {
        return $this->underlyingDriver->getSchemaManager($conn, $platform);
    }

    public function getExceptionConverter(): ExceptionConverter
    {
        return $this->underlyingDriver->getExceptionConverter();
    }
}
