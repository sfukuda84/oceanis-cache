<?php
namespace Oceanis\Cache\Pool;

use Oceanis\Cache\CachePool;

class Apcu implements CachePool
{

    public function getItem($key)
    {
        $item = apcu_fetch($key);
        if ($item === false) {
            return null;
        } else {
            return $item;
        }
    }

    public function getItems(array $keys = [])
    {
        $items = [];
        foreach ($keys as $key) {
            $item = apcu_fetch($key);
            if ($item !== false) {
                $items[$key] = $item;    
            }
        }
        return $items;
    }

    public function hasItem($key)
    {
        return apcu_exists($key);
    }

    public function clear()
    {
        return apcu_clear_cache();
    }

    public function deleteItem($key)
    {
        return !apcu_exists($key) || apcu_delete($key);
    }

    public function deleteItems(array $keys)
    {
        foreach ($keys as $key) {
            if (apcu_exists($key) && !apcu_delete($key)) {
                return false;
            }
        }
        return true;
    }

    public function save($key, $item, $ttl = null)
    {
        $ttl === null && $ttl = 86400000;
        return apcu_store($key, $item, $ttl);
    }

    public function saveDeferred($key, $item, $ttl = null)
    {
        $ttl === null && $ttl = 86400000;
        $this->deferreds[] = [$key, $item, $ttl];
        return true;
    }

    public function commit()
    {
        if (!empty($this->deferreds)) {
            foreach ($this->deferreds as $deferred) {
                if (!apcu_store($deferred[0], $deferred[1], $deferred[2])) {
                    $this->deferreds = [];
                    return false;
                }
            }
        }
        $this->deferreds = [];
        return true;
    }

    public function rollback()
    {
        $this->deferreds = [];
        return true;
    }
}
