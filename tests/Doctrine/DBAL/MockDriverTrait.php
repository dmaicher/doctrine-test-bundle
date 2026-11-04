<?php

declare(strict_types=1);

namespace Tests\Doctrine\DBAL;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\API\ExceptionConverter;
use Doctrine\DBAL\Driver\Connection as DriverConnection;
use Doctrine\DBAL\Driver\Middleware\AbstractConnectionMiddleware;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;

/**
 * @phpstan-ignore trait.unused
 */
trait MockDriverTrait
{
    /**
     * SQL executed by all connections returned by connect().
     *
     * @var list<string>
     */
    public array $executedSql = [];

    public function __construct(
        private DriverConnection $connection,
        private AbstractSchemaManager $schemaManager,
        private ExceptionConverter $exceptionConverter
    ) {
    }

    public function connect(array $params): DriverConnection
    {
        return new class(clone $this->connection, $this) extends AbstractConnectionMiddleware {
            public function __construct(DriverConnection $connection, private MockDriver $driver)
            {
                parent::__construct($connection);
            }

            public function exec(string $sql): int
            {
                $this->driver->executedSql[] = $sql;

                return 0;
            }
        };
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
