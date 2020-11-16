<?php

namespace DAMA\DoctrineTestBundle\Doctrine\DBAL;

use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Driver\DriverException;
use Doctrine\DBAL\Driver\ExceptionConverterDriver;
use Doctrine\DBAL\Exception;

abstract class AbstractStaticDriverV2 extends AbstractStaticDriver implements ExceptionConverterDriver
{
    /**
     * {@inheritdoc}
     */
    public function connect(array $params, $username = null, $password = null, array $driverOptions = []): Connection
    {
        if (!self::$keepStaticConnections) {
            return $this->underlyingDriver->connect($params, $username, $password, $driverOptions);
        }

        $key = $params['dama.connection_name'] ?? sha1(serialize($params).$username.$password);

        if (!isset(self::$connections[$key])) {
            self::$connections[$key] = $this->underlyingDriver->connect($params, $username, $password, $driverOptions);
            self::$connections[$key]->beginTransaction();
        }

        return new StaticConnection(self::$connections[$key]);
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return $this->underlyingDriver->getName();
    }

    /**
     * {@inheritdoc}
     */
    public function getDatabase(\Doctrine\DBAL\Connection $conn): ?string
    {
        return $this->underlyingDriver->getDatabase($conn);
    }

    /**
     * {@inheritdoc}
     */
    public function convertException($message, DriverException $exception): Exception\DriverException
    {
        if ($this->underlyingDriver instanceof ExceptionConverterDriver) {
            return $this->underlyingDriver->convertException($message, $exception);
        }

        return new Exception\DriverException($message, $exception);
    }

    public function getSchemaManager(\Doctrine\DBAL\Connection $conn)
    {
        return $this->underlyingDriver->getSchemaManager($conn);
    }
}
