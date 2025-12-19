<?php

namespace DAMA\DoctrineTestBundle\Doctrine\DBAL;

use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;

/**
 * @internal
 *
 * @phpstan-ignore trait.unused
 */
trait StaticConnectionTrait
{
    private bool $nested = false;

    public function __construct(private Connection $connection, private AbstractPlatform $platform)
    {
        parent::__construct($connection);
    }

    private function doBeginTransaction(): void
    {
        if ($this->nested) {
            throw new \BadMethodCallException(sprintf('Bad call to "%s". A savepoint is already in use for a nested transaction.', __METHOD__));
        }

        $this->exec($this->platform->createSavePoint('DAMA_TEST'));

        $this->nested = true;
    }

    private function doCommit(): void
    {
        if (!$this->nested) {
            throw new \BadMethodCallException(sprintf('Bad call to "%s". There is no savepoint for a nested transaction.', __METHOD__));
        }

        if ($this->platform->supportsReleaseSavepoints()) {
            $this->exec($this->platform->releaseSavePoint('DAMA_TEST'));
        }

        $this->nested = false;
    }

    private function doRollBack(): void
    {
        if (!$this->nested) {
            throw new \BadMethodCallException(sprintf('Bad call to "%s". There is no savepoint for a nested transaction.', __METHOD__));
        }

        $this->exec($this->platform->rollbackSavePoint('DAMA_TEST'));

        $this->nested = false;
    }

    public function getWrappedConnection(): Connection
    {
        return $this->connection;
    }
}
