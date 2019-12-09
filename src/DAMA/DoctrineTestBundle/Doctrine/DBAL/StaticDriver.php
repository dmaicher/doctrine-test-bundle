<?php

namespace DAMA\DoctrineTestBundle\Doctrine\DBAL;

use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Driver\DriverException;
use Doctrine\DBAL\Driver\ExceptionConverterDriver;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\VersionAwarePlatformDriver;

class StaticDriver implements Driver, ExceptionConverterDriver, VersionAwarePlatformDriver
{
    /**
     * @var Connection[]
     */
    private static $connections = [];

    /**
     * @var bool
     */
    private static $keepStaticConnections = false;

    /**
     * @var Driver
     */
    private $underlyingDriver;

    /**
     * @var AbstractPlatform
     */
    private $platform;

    public function __construct(Driver $underlyingDriver, AbstractPlatform $platform)
    {
        $this->underlyingDriver = $underlyingDriver;
        $this->platform = $platform;
    }

    /**
     * {@inheritdoc}
     */
    public function connect(array $params, $username = null, $password = null, array $driverOptions = []): Connection
    {
        if (self::$keepStaticConnections) {
            $key = sha1(serialize($params).$username.$password);

            if (!isset(self::$connections[$key])) {
                self::$connections[$key] = $this->underlyingDriver->connect($params, $username, $password, $driverOptions);
                self::$connections[$key]->beginTransaction();
            }

            return new StaticConnection(self::$connections[$key]);
        }

        return $this->underlyingDriver->connect($params, $username, $password, $driverOptions);
    }

    /**
     * {@inheritdoc}
     */
    public function getDatabasePlatform(): AbstractPlatform
    {
        return $this->platform;
    }

    /**
     * {@inheritdoc}
     */
    public function getSchemaManager(\Doctrine\DBAL\Connection $conn): AbstractSchemaManager
    {
        return $this->underlyingDriver->getSchemaManager($conn);
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return $this->underlyingDriver->getName();
    }

    /**
     * {@inheritdoc}
     */
    public function getDatabase(\Doctrine\DBAL\Connection $conn): string
    {
        return $this->underlyingDriver->getDatabase($conn);
    }

    /**
     * {@inheritdoc}
     */
    public function convertException($message, DriverException $exception): Exception\DriverException
    {
        if ($this->underlyingDriver instanceof ExceptionConverterDriver) {
            return $this->underlyingDriver->convertException($message, $exception);
        }

        return new Exception\DriverException($message, $exception);
    }

    /**
     * {@inheritdoc}
     */
    public function createDatabasePlatformForVersion($version): AbstractPlatform
    {
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
