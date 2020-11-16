<?php

namespace DAMA\DoctrineTestBundle\Doctrine\DBAL;

use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\VersionAwarePlatformDriver;

/**
 * @internal
 */
abstract class AbstractStaticDriver implements Driver, VersionAwarePlatformDriver
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
