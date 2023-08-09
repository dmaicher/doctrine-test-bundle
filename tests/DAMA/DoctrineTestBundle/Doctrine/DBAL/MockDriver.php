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
    private function getMock(string $class)
    {
        // TODO: remove this once we drop support for PHPUnit < 10
        $generatorClass = class_exists('PHPUnit\Framework\MockObject\Generator')
            ? 'PHPUnit\Framework\MockObject\Generator'
            : 'PHPUnit\Framework\MockObject\Generator\Generator';

        /** @phpstan-ignore-next-line */
        return (new $generatorClass())->getMock(
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
    public function connect(array $params): \Doctrine\DBAL\Driver\Connection
    {
        return $this->getMock(Driver\Connection::class);
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
