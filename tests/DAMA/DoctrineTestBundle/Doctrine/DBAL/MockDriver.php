<?php

namespace Tests\DAMA\DoctrineTestBundle\Doctrine\DBAL;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\API\ExceptionConverter;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySQL80Platform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;

class MockDriver implements Driver
{
    private $connection;
    private $schemaManager;
    private $exceptionConverter;

    /**
     * @param Driver\Connection     $connection
     * @param AbstractSchemaManager $schemaManager
     * @param ExceptionConverter    $exceptionConverter
     */
    public function __construct(
        $connection,
        $schemaManager,
        $exceptionConverter
    ) {
        $this->connection = $connection;
        $this->schemaManager = $schemaManager;
        $this->exceptionConverter = $exceptionConverter;
    }

    /**
     * {@inheritdoc}
     */
    public function connect(array $params): Driver\Connection
    {
        return clone $this->connection;
    }

    /**
     * {@inheritdoc}
     */
    public function getDatabasePlatform(): AbstractPlatform
    {
        return new MySQL80Platform();
    }

    /**
     * {@inheritdoc}
     */
    public function getSchemaManager(Connection $conn, AbstractPlatform $platform): AbstractSchemaManager
    {
        return $this->schemaManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'mock';
    }

    /**
     * {@inheritdoc}
     */
    public function getDatabase(Connection $conn): string
    {
        return 'mock';
    }

    public function getExceptionConverter(): ExceptionConverter
    {
        return $this->exceptionConverter;
    }
}
