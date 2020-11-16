<?php

namespace Tests\DAMA\DoctrineTestBundle\Doctrine\DBAL;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use PHPUnit\Framework\MockObject\Generator;

/**
 * @internal
 */
class MockDriverV2 implements Driver
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
    public function connect(array $params, $username = null, $password = null, array $driverOptions = []): Driver\Connection
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
    public function getSchemaManager(Connection $conn): AbstractSchemaManager
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
}
