<?php

namespace DAMA\DoctrineTestBundle\Doctrine\DBAL;

use Doctrine\Bundle\DoctrineBundle\ConnectionFactory;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Events;

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

        if (!StaticDriver::isKeepStaticConnections() || !isset($params['dama.keep_static']) || !$params['dama.keep_static']) {
            return $connectionOriginalDriver;
        }

        // wrapper class can be overridden/customized in params (see Doctrine\DBAL\DriverManager)
        $connectionWrapperClass = get_class($connectionOriginalDriver);

        /** @var Connection $connection */
        $connection = new $connectionWrapperClass(
            $connectionOriginalDriver->getParams(),
            new StaticDriver($connectionOriginalDriver->getDriver(), $connectionOriginalDriver->getDatabasePlatform()),
            $connectionOriginalDriver->getConfiguration(),
            $connectionOriginalDriver->getEventManager()
        );

        $connection->getEventManager()->addEventListener(Events::postConnect, new PostConnectEventListener());

        // Make sure we use savepoints to be able to easily roll-back nested transactions
        if ($connection->getDriver()->getDatabasePlatform()->supportsSavepoints()) {
            $connection->setNestTransactionsWithSavepoints(true);
        }

        return $connection;
    }
}
