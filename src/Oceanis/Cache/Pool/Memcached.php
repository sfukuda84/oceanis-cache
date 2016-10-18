<?php
namespace Oceanis\Cache\Pool;

use Oceanis\Cache\CachePool;

class Memcached implements CachePool
{

    public function __construct(\Memcached $conn)
    {
        $this->conn = $conn;
    }


    public function getItem($key)
    {
    }

    public function getItems(array $keys)
    {
    }

    public function hasItem($key)
    {
    }

    public function clear()
    {
    }

    public function deleteItem($key)
    {
    }

    public function deleteItems(array $keys)
    {
    }

    public function save($key, $item, $ttl = null)
    {
    }

    public function saveDeferred($key, $item, $ttl = null)
    {
    }

    public function commit()
    {
    }

    public function rollback()
    {
    }
}
