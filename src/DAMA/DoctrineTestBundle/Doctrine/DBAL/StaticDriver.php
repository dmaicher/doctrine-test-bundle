<?php

namespace DAMA\DoctrineTestBundle\Doctrine\DBAL;

use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Driver\Connection as DriverConnection;

class StaticDriver extends Driver\Middleware\AbstractDriverMiddleware
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

    public function __construct(Driver $underlyingDriver)
    {
        $this->underlyingDriver = $underlyingDriver;
        parent::__construct($underlyingDriver);
    }

    public function connect(array $params): DriverConnection
    {
        if (!self::isKeepStaticConnections()
            || !isset($params['dama.keep_static'])
            || !$params['dama.keep_static']
        ) {
            return parent::connect($params);
        }

        $key = sha1(json_encode($params));

        if (!isset(self::$connections[$key])) {
            self::$connections[$key] = parent::connect($params);
            self::$connections[$key]->beginTransaction();
        }

        $connection = self::$connections[$key];

        $platform = isset($params['serverVersion'])
            ? $this->createDatabasePlatformForVersion($params['serverVersion'])
            : $this->getDatabasePlatform();

        if (!$platform->supportsSavepoints() || !$platform->supportsReleaseSavepoints()) {
            throw new \RuntimeException('This bundle only works for database platforms that support savepoints.');
        }

        return new StaticConnection($connection, $platform);
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
