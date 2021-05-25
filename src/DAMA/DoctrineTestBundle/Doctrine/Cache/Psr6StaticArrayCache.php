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
    private static $adaptersByNamespace;

    /**
     * @var ArrayAdapter
     */
    private $adapter;

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

    /**
     * {@inheritdoc}
     */
    public function getItem($key)
    {
        return $this->adapter->getItem($key);
    }

    /**
     * {@inheritdoc}
     */
    public function getItems(array $keys = [])
    {
        return $this->adapter->getItems($keys);
    }

    /**
     * {@inheritdoc}
     */
    public function hasItem($key)
    {
        return $this->adapter->hasItem($key);
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        return $this->adapter->clear();
    }

    /**
     * {@inheritdoc}
     */
    public function deleteItem($key)
    {
        return $this->adapter->deleteItem($key);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteItems(array $keys)
    {
        return $this->adapter->deleteItems($keys);
    }

    /**
     * {@inheritdoc}
     */
    public function save(CacheItemInterface $item)
    {
        return $this->adapter->save($item);
    }

    /**
     * {@inheritdoc}
     */
    public function saveDeferred(CacheItemInterface $item)
    {
        return $this->adapter->saveDeferred($item);
    }

    /**
     * {@inheritdoc}
     */
    public function commit()
    {
        return $this->adapter->commit();
    }
}
