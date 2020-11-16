<?php

namespace Tests\DAMA\DoctrineTestBundle\Doctrine\DBAL;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\API\ExceptionConverter;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use PHPUnit\Framework\MockObject\Generator;

/**
 * @internal
 */
class MockDriverV3 implements Driver
{
    private function getMock(string $class)
    {
        return (new Generator())->getMock(
            $class,
            [],
            [],
            '',
            false
        );
    }

    /**
     * {@inheritdoc}
     */
    public function connect(array $params): Driver\Connection
    {
        return $this->getMock(Driver\Connection::class);
    }

    /**
     * {@inheritdoc}
     */
    public function getDatabasePlatform(): AbstractPlatform
    {
        return $this->getMock(AbstractPlatform::class);
    }

    /**
     * {@inheritdoc}
     */
    public function getSchemaManager(Connection $conn, AbstractPlatform $platform): AbstractSchemaManager
    {
        return $this->getMock(AbstractSchemaManager::class);
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
        return $this->getMock(ExceptionConverter::class);
    }
}
