<?php

namespace DAMA\DoctrineTestBundle\Doctrine\DBAL;

use Doctrine\DBAL\Driver\Result;
use Doctrine\DBAL\Driver\Statement;

/**
 * @internal
 */
abstract class AbstractStaticConnectionV3 extends AbstractStaticConnection
{
    /**
     * {@inheritdoc}
     */
    public function prepare(string $prepareString): Statement
    {
        return $this->connection->prepare($prepareString);
    }

    /**
     * {@inheritdoc}
     */
    public function query(string $sql): Result
    {
        return $this->connection->query($sql);
    }

    /**
     * {@inheritdoc}
     */
    public function exec(string $statement): int
    {
        return $this->connection->exec($statement);
    }
}
