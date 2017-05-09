<?php

namespace DAMA\DoctrineTestBundle\Doctrine\DBAL;

use Doctrine\Bundle\DoctrineBundle\ConnectionFactory;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;

class StaticConnectionFactory extends ConnectionFactory
{
    /**
     * @param array         $params
     * @param Configuration $config
     * @param EventManager  $eventManager
     * @param array         $mappingTypes
     *
     * @return \Doctrine\DBAL\Connection
     */
    public function createConnection(array $params, Configuration $config = null, EventManager $eventManager = null, array $mappingTypes = array())
    {
        // create the original connection to get the used wrapper class + driver
        $connectionOriginalDriver = parent::createConnection($params, $config, $eventManager, $mappingTypes);

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
            // So we start it as well on this connection so the internal state ($_transactionNestingLevel) is in sync with the underlying connection.
            $connection->beginTransaction();
        }

        return $connection;
    }
}
