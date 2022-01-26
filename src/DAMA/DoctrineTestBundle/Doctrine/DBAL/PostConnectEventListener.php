<?php

namespace DAMA\DoctrineTestBundle\Doctrine\DBAL;

use Doctrine\DBAL\Event\ConnectionEventArgs;

class PostConnectEventListener
{
    public function postConnect(ConnectionEventArgs $args): void
    {
        // The underlying connection already has a transaction started.
        // We start a transaction on the connection as well
        // so the internal state ($_transactionNestingLevel) is in sync with the underlying connection.
        $args->getConnection()->beginTransaction();
    }
}
