<?php

namespace DAMA\DoctrineTestBundle\Doctrine\DBAL;

use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Driver\Middleware\AbstractConnectionMiddleware;

/**
 * Wraps a real connection and just skips the first call to beginTransaction as a transaction is already started on the underlying connection.
 */
class StaticConnection extends AbstractConnectionMiddleware
{
    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var bool
     */
    protected $transactionStarted = false;

    public function __construct(Connection $connection)
    {
        parent::__construct($connection);
        $this->connection = $connection;
    }

    public function beginTransaction(): bool
    {
        if ($this->transactionStarted) {
            return $this->connection->beginTransaction();
        }

        return $this->transactionStarted = true;
    }

    public function getWrappedConnection(): Connection
    {
        return $this->connection;
    }
}
