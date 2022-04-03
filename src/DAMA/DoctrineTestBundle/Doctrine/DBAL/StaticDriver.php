<?php

namespace DAMA\DoctrineTestBundle\Doctrine\DBAL;

use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\API\ExceptionConverter;
use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Driver\Connection as DriverConnection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\VersionAwarePlatformDriver;

class StaticDriver implements Driver, VersionAwarePlatformDriver
{
    /**
     * @var Connection[]
     */
    protected static $connections = [];

    /**
     * @var bool
     */
    protected static $keepStaticConnections = false;

    /**
     * @var Driver
     */
    protected $underlyingDriver;

    /**
     * @var AbstractPlatform
     */
    protected $platform;

    public function __construct(Driver $underlyingDriver, AbstractPlatform $platform)
    {
        $this->underlyingDriver = $underlyingDriver;
        $this->platform = $platform;
    }

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

    public function getSchemaManager(\Doctrine\DBAL\Connection $conn, AbstractPlatform $platform): AbstractSchemaManager
    {
        return $this->underlyingDriver->getSchemaManager($conn, $platform);
    }

    public function getExceptionConverter(): ExceptionConverter
    {
        return $this->underlyingDriver->getExceptionConverter();
    }

    public function getDatabasePlatform(): AbstractPlatform
    {
        return $this->platform;
    }

    public function createDatabasePlatformForVersion($version): AbstractPlatform
    {
        if ($this->underlyingDriver instanceof VersionAwarePlatformDriver) {
            return $this->underlyingDriver->createDatabasePlatformForVersion($version);
        }

        return $this->platform;
    }

    public static function setKeepStaticConnections(bool $keepStaticConnections): void
    {
        self::$keepStaticConnections = $keepStaticConnections;
    }

    public static function isKeepStaticConnections(): bool
    {
        return self::$keepStaticConnections;
    }

    public static function beginTransaction(): void
    {
        foreach (self::$connections as $con) {
            $con->beginTransaction();
        }
    }

    public static function rollBack(): void
    {
        foreach (self::$connections as $con) {
            $con->rollBack();
        }
    }

    public static function commit(): void
    {
        foreach (self::$connections as $con) {
            $con->commit();
        }
    }
}
