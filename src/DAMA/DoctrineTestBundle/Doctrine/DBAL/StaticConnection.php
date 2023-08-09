<?php

namespace DAMA\DoctrineTestBundle\Doctrine\DBAL;

use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Driver\Middleware\AbstractConnectionMiddleware;
use Doctrine\DBAL\Platforms\AbstractPlatform;

/**
 * Wraps a real connection and makes sure the initial nested transaction is using a savepoint.
 */
class StaticConnection extends AbstractConnectionMiddleware
{
    private const SAVEPOINT_NAME = 'DAMA_TEST';

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var AbstractPlatform
     */
    private $platform;

    /**
     * @var bool
     */
    private $nested = false;

    public function __construct(Connection $connection, AbstractPlatform $platform)
    {
        parent::__construct($connection);
        $this->connection = $connection;
        $this->platform = $platform;
    }

    public function beginTransaction(): bool
    {
        if ($this->nested) {
            throw new \BadMethodCallException(sprintf('Bad call to "%s". A savepoint is already in use for a nested transaction.', __METHOD__));
        }

        $this->exec($this->platform->createSavePoint(self::SAVEPOINT_NAME));

        $this->nested = true;

        return true;
    }

    public function commit(): bool
    {
        if (!$this->nested) {
            throw new \BadMethodCallException(sprintf('Bad call to "%s". There is no savepoint for a nested transaction.', __METHOD__));
        }

        if ($this->platform->supportsReleaseSavepoints()) {
            $this->exec($this->platform->releaseSavePoint(self::SAVEPOINT_NAME));
        }

        $this->nested = false;

        return true;
    }

    public function rollBack(): bool
    {
        if (!$this->nested) {
            throw new \BadMethodCallException(sprintf('Bad call to "%s". There is no savepoint for a nested transaction.', __METHOD__));
        }

        $this->exec($this->platform->rollbackSavePoint(self::SAVEPOINT_NAME));

        $this->nested = false;

        return true;
    }

    public function getWrappedConnection(): Connection
    {
        return $this->connection;
    }
}
