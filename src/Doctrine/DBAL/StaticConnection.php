<?php

namespace DAMA\DoctrineTestBundle\Doctrine\DBAL;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Middleware\AbstractConnectionMiddleware;

/**
 * Wraps a real connection and makes sure the initial nested transaction is using a savepoint.
 */
if (method_exists(Connection::class, 'getEventManager')) {
    // DBAL < 4
    /**
     * @final
     */
    class StaticConnection extends AbstractConnectionMiddleware
    {
        use StaticConnectionTrait;

        public function beginTransaction(): bool
        {
            $this->doBeginTransaction();

            return true;
        }

        public function commit(): bool
        {
            $this->doCommit();

            return true;
        }

        public function rollBack(): bool
        {
            $this->doRollBack();

            return true;
        }
    }
} else {
    // DBAL >= 4
    /**
     * @final
     */
    class StaticConnection extends AbstractConnectionMiddleware
    {
        use StaticConnectionTrait;

        public function beginTransaction(): void
        {
            $this->doBeginTransaction();
        }

        public function commit(): void
        {
            $this->doCommit();
        }

        public function rollBack(): void
        {
            $this->doRollBack();
        }
    }
}
