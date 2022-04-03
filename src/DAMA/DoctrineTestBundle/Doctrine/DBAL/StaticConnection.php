<?php

namespace DAMA\DoctrineTestBundle\Doctrine\DBAL;

use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Driver\Result;
use Doctrine\DBAL\Driver\Statement;
use Doctrine\DBAL\ParameterType;

/**
 * Wraps a real connection and just skips the first call to beginTransaction as a transaction is already started on the underlying connection.
 */
class StaticConnection implements Connection
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
        $this->connection = $connection;
    }

    public function lastInsertId($name = null): string
    {
        return $this->connection->lastInsertId($name);
    }

    public function beginTransaction(): bool
    {
        if ($this->transactionStarted) {
            return $this->connection->beginTransaction();
        }

        return $this->transactionStarted = true;
    }

    public function commit(): bool
    {
        return $this->connection->commit();
    }

    public function rollBack(): bool
    {
        return $this->connection->rollBack();
    }

    public function getWrappedConnection(): Connection
    {
        return $this->connection;
    }

    public function getNativeConnection()
    {
        if (!method_exists($this->connection, 'getNativeConnection')) {
            throw new \LogicException(sprintf('The driver connection %s does not support accessing the native connection.', get_class($this->connection)));
        }

        return $this->connection->getNativeConnection();
    }

    /**
     * @return mixed
     */
    public function quote($input, $type = ParameterType::STRING)
    {
        return $this->connection->quote($input, $type);
    }

    public function prepare(string $prepareString): Statement
    {
        return $this->connection->prepare($prepareString);
    }

    public function query(string $sql): Result
    {
        return $this->connection->query($sql);
    }

    public function exec(string $statement): int
    {
        return $this->connection->exec($statement);
    }
}
