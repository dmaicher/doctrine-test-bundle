<?php

namespace Tests\DAMA\DoctrineTestBundle\Doctrine\Cache;

use DAMA\DoctrineTestBundle\Doctrine\Cache\Psr6StaticArrayCache;
use PHPUnit\Framework\TestCase;

class Psr6StaticArrayCacheTest extends TestCase
{
    public function testKeepsCacheItemsStatically(): void
    {
        Psr6StaticArrayCache::reset();
        $cache = new Psr6StaticArrayCache('foo');

        $item = $cache->getItem('one');
        $this->assertFalse($item->isHit());

        $value = new \stdClass();
        $item->set($value);
        $cache->save($item);

        $cache = new Psr6StaticArrayCache('bar');
        $this->assertFalse($cache->getItem('one')->isHit());

        $cache = new Psr6StaticArrayCache('foo');
        $this->assertTrue($cache->getItem('one')->isHit());
        $this->assertSame($value, $cache->getItem('one')->get());
    }
}
