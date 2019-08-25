<?php

namespace DAMA\DoctrineTestBundle\Doctrine\DBAL;

use Doctrine\Bundle\DoctrineBundle\ConnectionFactory;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;

class StaticConnectionFactory extends ConnectionFactory
{
    /**
     * @var ConnectionFactory
     */
    private $decoratedFactory;

    public function __construct(ConnectionFactory $decoratedFactory)
    {
        parent::__construct([]);
        $this->decoratedFactory = $decoratedFactory;
    }

    public function createConnection(array $params, Configuration $config = null, EventManager $eventManager = null, array $mappingTypes = []): Connection
    {
        // create the original connection to get the used wrapper class + driver
        $connectionOriginalDriver = $this->decoratedFactory->createConnection($params, $config, $eventManager, $mappingTypes);

        // wrapper class can be overridden/customized in params (see Doctrine\DBAL\DriverManager)
        $connectionWrapperClass = get_class($connectionOriginalDriver);

        /** @var Connection $connection */
        $connection = new $connectionWrapperClass(
            $connectionOriginalDriver->getParams(),
            new StaticDriver($connectionOriginalDriver->getDriver(), $connectionOriginalDriver->getDatabasePlatform()),
            $connectionOriginalDriver->getConfiguration(),
            $connectionOriginalDriver->getEventManager()
        );

        if (StaticDriver::isKeepStaticConnections()) {
            // The underlying connection already has a transaction started.
            // Make sure we use savepoints to be able to easily roll-back nested transactions
            if ($connection->getDriver()->getDatabasePlatform()->supportsSavepoints()) {
                $connection->setNestTransactionsWithSavepoints(true);
            }

            // We start a transaction on the connection as well
            // so the internal state ($_transactionNestingLevel) is in sync with the underlying connection.
            $connection->beginTransaction();
        }

        return $connection;
    }
}
