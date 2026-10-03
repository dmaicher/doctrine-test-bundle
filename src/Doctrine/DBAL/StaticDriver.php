<?php

namespace DAMA\DoctrineTestBundle\Doctrine\DBAL;

use Doctrine\DBAL\Connection\StaticServerVersionProvider;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;

/**
 * @final
 */
class StaticDriver extends Driver\Middleware\AbstractDriverMiddleware
{
    /**
     * @var array<string, Connection>
     */
    private static array $connections = [];

    private static bool $keepStaticConnections = false;

    /**
     * Number of transactions per connection key that were started by application code and are still open.
     *
     * @var array<string, int>
     */
    private static array $openTransactions = [];

    public function connect(array $params): Connection
    {
        if (!self::isKeepStaticConnections() || !isset($params['dama.connection_key'])) {
            return parent::connect($params);
        }

        /** @var string $key */
        $key = $params['dama.connection_key'];

        if (!isset(self::$connections[$key])) {
            self::$connections[$key] = parent::connect($params);
            self::$connections[$key]->beginTransaction();
        }

        $connection = self::$connections[$key];

        $platform = $this->getPlatform($connection, $params);

        if (!$platform->supportsSavepoints()) {
            throw new \RuntimeException('This bundle only works for database platforms that support savepoints.');
        }

        return new StaticConnection($connection, $platform, $key);
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
        self::$openTransactions = [];

        foreach (self::$connections as $connection) {
            $connection->beginTransaction();
        }
    }

    public static function rollBack(): void
    {
        self::$openTransactions = [];

        foreach (self::$connections as $connection) {
            $connection->rollBack();
        }
    }

    public static function commit(): void
    {
        self::$openTransactions = [];

        foreach (self::$connections as $connection) {
            $connection->commit();
        }
    }

    /**
     * @internal
     */
    public static function transactionStarted(string $key): void
    {
        self::$openTransactions[$key] = (self::$openTransactions[$key] ?? 0) + 1;
    }

    /**
     * @internal
     */
    public static function transactionEnded(string $key): void
    {
        // it might have been started before the static transaction of the current test began
        if (!isset(self::$openTransactions[$key])) {
            return;
        }

        if (--self::$openTransactions[$key] === 0) {
            unset(self::$openTransactions[$key]);
        }
    }

    /**
     * Returns the keys of all connections that still have a transaction open which was started by application code.
     *
     * @return list<string>
     */
    public static function getConnectionKeysWithOpenTransactions(): array
    {
        return array_keys(self::$openTransactions);
    }

    private function getPlatform(Connection $connection, array $params): AbstractPlatform
    {
        if (isset($params['platform'])) {
            return $params['platform'];
        }

        // DBAL 3
        if (method_exists($this, 'createDatabasePlatformForVersion')) {
            if (isset($params['serverVersion'])) {
                return $this->createDatabasePlatformForVersion($params['serverVersion']);
            }

            return $this->getDatabasePlatform();
        }

        // DBAL 4
        return $this->getDatabasePlatform(
            isset($params['serverVersion'])
                ? new StaticServerVersionProvider($params['serverVersion'])
                : $connection,
        );
    }
}
