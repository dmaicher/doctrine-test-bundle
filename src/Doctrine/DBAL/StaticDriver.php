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

    /**
     * @var array<string, AbstractPlatform>
     */
    private static array $platforms = [];

    /**
     * Number of savepoint levels created with createSavepoint() within the current static transaction.
     */
    private static int $savepointLevel = 0;

    public function connect(array $params): Connection
    {
        if (!self::isKeepStaticConnections() || !isset($params['dama.connection_key'])) {
            return parent::connect($params);
        }

        /** @var string $key */
        $key = $params['dama.connection_key'];

        if (!isset(self::$connections[$key])) {
            $connection = parent::connect($params);
            $platform = $this->getPlatform($connection, $params);

            if (!$platform->supportsSavepoints()) {
                throw new \RuntimeException('This bundle only works for database platforms that support savepoints.');
            }

            $connection->beginTransaction();

            // connections are opened lazily, on first use: one opened after savepoint levels were
            // created (e.g. a second connection first used by a test) must get them as well
            for ($level = 1; $level <= self::$savepointLevel; ++$level) {
                $connection->exec($platform->createSavePoint(self::savepointName($level)));
            }

            self::$connections[$key] = $connection;
            self::$platforms[$key] = $platform;
        }

        return new StaticConnection(self::$connections[$key], self::$platforms[$key], $key);
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
        self::$savepointLevel = 0;

        foreach (self::$connections as $connection) {
            $connection->beginTransaction();
        }
    }

    public static function rollBack(): void
    {
        self::$openTransactions = [];
        self::$savepointLevel = 0;

        foreach (self::$connections as $connection) {
            $connection->rollBack();
        }
    }

    public static function commit(): void
    {
        self::$openTransactions = [];
        self::$savepointLevel = 0;

        foreach (self::$connections as $connection) {
            $connection->commit();
        }
    }

    /**
     * Creates a new savepoint level on all static connections: the PHPUnit extension then rolls back to this level
     * before each test instead of rolling back the whole static transaction.
     */
    public static function createSavepoint(): void
    {
        $name = self::savepointName(self::$savepointLevel + 1);

        foreach (self::$connections as $key => $connection) {
            $connection->exec(self::$platforms[$key]->createSavePoint($name));
        }

        ++self::$savepointLevel;
    }

    /**
     * Rolls back to the innermost savepoint level, which is kept.
     */
    public static function rollBackToSavepoint(): void
    {
        if (self::$savepointLevel === 0) {
            throw new \LogicException('There is no savepoint level.');
        }

        self::$openTransactions = [];
        $name = self::savepointName(self::$savepointLevel);

        foreach (self::$connections as $key => $connection) {
            $connection->exec(self::$platforms[$key]->rollbackSavePoint($name));
        }
    }

    /**
     * Rolls back to the innermost savepoint level and removes it.
     */
    public static function releaseSavepoint(): void
    {
        self::rollBackToSavepoint();

        $name = self::savepointName(self::$savepointLevel);

        foreach (self::$connections as $key => $connection) {
            if (self::$platforms[$key]->supportsReleaseSavepoints()) {
                $connection->exec(self::$platforms[$key]->releaseSavePoint($name));
            }
        }

        --self::$savepointLevel;
    }

    public static function getSavepointLevel(): int
    {
        return self::$savepointLevel;
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

    private static function savepointName(int $level): string
    {
        return 'DAMA_LEVEL_'.$level;
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
