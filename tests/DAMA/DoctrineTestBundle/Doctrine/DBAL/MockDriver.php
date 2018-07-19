<?php

namespace Tests\DAMA\DoctrineTestBundle\Doctrine\DBAL;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use PHPUnit\Framework\MockObject\Generator;

class MockDriver implements Driver
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
    public function connect(array $params, $username = null, $password = null, array $driverOptions = [])
    {
        return $this->getMock(Driver\Connection::class);
    }

    /**
     * {@inheritdoc}
     */
    public function getDatabasePlatform()
    {
        return $this->getMock(AbstractPlatform::class);
    }

    /**
     * {@inheritdoc}
     */
    public function getSchemaManager(Connection $conn)
    {
        return $this->getMock(AbstractSchemaManager::class);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'mock';
    }

    /**
     * {@inheritdoc}
     */
    public function getDatabase(Connection $conn)
    {
        return 'mock';
    }
}
