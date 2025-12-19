<?php

namespace DAMA\DoctrineTestBundle\Doctrine\Cache;

use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

final class Psr6StaticArrayCache implements CacheItemPoolInterface
{
    /**
     * @var array<string, ArrayAdapter>
     */
    private static array $adaptersByNamespace;

    private ArrayAdapter $adapter;

    public function __construct(string $namespace)
    {
        if (!isset(self::$adaptersByNamespace[$namespace])) {
            self::$adaptersByNamespace[$namespace] = new ArrayAdapter(0, false);
        }
        $this->adapter = self::$adaptersByNamespace[$namespace];
    }

    /**
     * @internal
     */
    public static function reset(): void
    {
        self::$adaptersByNamespace = [];
    }

    public function getItem($key): CacheItemInterface
    {
        return $this->adapter->getItem($key);
    }

    /**
     * @return iterable<CacheItemInterface>
     */
    public function getItems(array $keys = []): iterable
    {
        return $this->adapter->getItems($keys);
    }

    public function hasItem($key): bool
    {
        return $this->adapter->hasItem($key);
    }

    public function clear(): bool
    {
        return $this->adapter->clear();
    }

    public function deleteItem($key): bool
    {
        return $this->adapter->deleteItem($key);
    }

    public function deleteItems(array $keys): bool
    {
        return $this->adapter->deleteItems($keys);
    }

    public function save(CacheItemInterface $item): bool
    {
        return $this->adapter->save($item);
    }

    public function saveDeferred(CacheItemInterface $item): bool
    {
        return $this->adapter->saveDeferred($item);
    }

    public function commit(): bool
    {
        return $this->adapter->commit();
    }
}
