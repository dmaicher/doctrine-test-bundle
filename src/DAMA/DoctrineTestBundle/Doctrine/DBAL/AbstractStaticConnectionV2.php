<?php

namespace DAMA\DoctrineTestBundle\Doctrine\DBAL;

use Doctrine\DBAL\Driver\Statement;

/**
 * @internal
 */
abstract class AbstractStaticConnectionV2 extends AbstractStaticConnection
{
    public function prepare($prepareString): Statement
    {
        return $this->connection->prepare($prepareString);
    }

    public function query(): Statement
    {
        return call_user_func_array([$this->connection, 'query'], func_get_args());
    }

    public function exec($statement): int
    {
        return $this->connection->exec($statement);
    }

    public function errorCode(): ?string
    {
        return $this->connection->errorCode();
    }

    public function errorInfo(): array
    {
        return $this->connection->errorInfo();
    }
}
