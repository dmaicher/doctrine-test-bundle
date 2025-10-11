<?php

declare(strict_types=1);

namespace Tests\Doctrine\DBAL;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\API\ExceptionConverter;
use Doctrine\DBAL\Driver\Connection as DriverConnection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;

/**
 * @phpstan-ignore trait.unused
 */
trait MockDriverTrait
{
    public function __construct(
        private DriverConnection $connection,
        private AbstractSchemaManager $schemaManager,
        private ExceptionConverter $exceptionConverter
    ) {
    }

    public function connect(array $params): DriverConnection
    {
        return clone $this->connection;
    }

    public function getSchemaManager(Connection $conn, AbstractPlatform $platform): AbstractSchemaManager
    {
        return $this->schemaManager;
    }

    public function getName(): string
    {
        return 'mock';
    }

    public function getDatabase(Connection $conn): string
    {
        return 'mock';
    }

    public function getExceptionConverter(): ExceptionConverter
    {
        return $this->exceptionConverter;
    }
}
