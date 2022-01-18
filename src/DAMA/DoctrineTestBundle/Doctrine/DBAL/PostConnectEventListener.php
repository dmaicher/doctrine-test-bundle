<?php

namespace DAMA\DoctrineTestBundle\Doctrine\DBAL;

use Doctrine\DBAL\Event\ConnectionEventArgs;

class PostConnectEventListener
{
    public function postConnect(ConnectionEventArgs $args): void
    {
        $connection = $args->getConnection();
        if (!$connection->getDriver() instanceof StaticDriver) {
            return;
        }

        // The underlying connection already has a transaction started.
        // We start a transaction on the connection as well
        // so the internal state ($_transactionNestingLevel) is in sync with the underlying connection.
        $connection->beginTransaction();
    }
}
