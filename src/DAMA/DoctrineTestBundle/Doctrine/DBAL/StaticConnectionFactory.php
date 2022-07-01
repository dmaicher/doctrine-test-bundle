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
        $connection = $this->decoratedFactory->createConnection($params, $config, $eventManager, $mappingTypes);

        if (!StaticDriver::isKeepStaticConnections() || !isset($params['dama.keep_static']) || !$params['dama.keep_static']) {
            return $connection;
        }

        // Make sure we use savepoints to be able to easily roll-back nested transactions
        if ($connection->getDriver()->getDatabasePlatform()->supportsSavepoints()) {
            $connection->setNestTransactionsWithSavepoints(true);
        }

        return $connection;
    }
}
