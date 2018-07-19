<?php

namespace DAMA\DoctrineTestBundle\Doctrine\DBAL;

use Doctrine\DBAL\Driver\Connection;

/**
 * Wraps a real connection and just skips the first call to beginTransaction as a transaction is already started on the underlying connection.
 */
class StaticConnection implements Connection
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var bool
     */
    private $transactionStarted = false;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * {@inheritdoc}
     */
    public function prepare($prepareString)
    {
        return $this->connection->prepare($prepareString);
    }

    /**
     * {@inheritdoc}
     */
    public function query()
    {
        return call_user_func_array([$this->connection, 'query'], func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function quote($input, $type = \PDO::PARAM_STR)
    {
        return $this->connection->quote($input, $type);
    }

    /**
     * {@inheritdoc}
     */
    public function exec($statement)
    {
        return $this->connection->exec($statement);
    }

    /**
     * {@inheritdoc}
     */
    public function lastInsertId($name = null)
    {
        return $this->connection->lastInsertId($name);
    }

    /**
     * {@inheritdoc}
     */
    public function beginTransaction()
    {
        if ($this->transactionStarted) {
            return $this->connection->beginTransaction();
        }

        return $this->transactionStarted = true;
    }

    /**
     * {@inheritdoc}
     */
    public function commit()
    {
        return $this->connection->commit();
    }

    /**
     * {@inheritdoc}
     */
    public function rollBack()
    {
        return $this->connection->rollBack();
    }

    /**
     * {@inheritdoc}
     */
    public function errorCode()
    {
        return $this->connection->errorCode();
    }

    /**
     * {@inheritdoc}
     */
    public function errorInfo()
    {
        return $this->connection->errorInfo();
    }

    /**
     * @return Connection
     */
    public function getWrappedConnection()
    {
        return $this->connection;
    }
}
