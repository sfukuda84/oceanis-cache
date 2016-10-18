<?php
namespace Oceanis\Cache\Pool;

use Oceanis\Cache\CachePool;

class Redis implements CachePool
{
    protected $conn;
    protected $prefix;
    protected $derferreds;

    public function __construct($conn, $prefix = 'cache')
    {
        $this->conn = $conn;
        $this->prefix = $prefix;
    }

    public function getItem($key)
    {
        $item = $this->conn->hGet($this->prefix, $key);
        if ($item === false) {
            return null;
        }
        $pos = strpos($item, ':');
        $ttl = substr($item, 0, $pos);
        if ($ttl < time()) {
            $this->conn->hDel($this->prefix, $key);
            return null;
        }
        return unserialize(substr($item, $pos + 1));
    }

    public function getItems(array $keys)
    {
        $items = [];
        $time = time();
        if ($tmp = $this->conn->hMGet($this->prefix, $keys)) {
            foreach ($tmp as $key => $val) {
                if ($val === false) continue;
                $pos = strpos($val, ':');
                $ttl = substr($val, 0, $pos);
                if ($ttl < time()) {
                    $this->conn->hDel($this->prefix, $key);
                    continue;
                }
                $items[$key] = unserialize(substr($val, $pos + 1));
            }
        }
        return $items;
    }

    public function hasItem($key)
    {
        return $this->conn->hExists($this->prefix, $key);
    }

    public function clear()
    {
        return $this->conn->del($this->prefix) !== false;
    }

    public function deleteItem($key)
    {
        return $this->conn->hDel($this->prefix, $key) !== false;
    }

    public function deleteItems(array $keys)
    {
        $ret = true;
        foreach ($keys as $key) {
            if ($this->conn->hDel($this->prefix, $key) === false) {
                $ret = false;
            }
        }
        return $ret;
    }

    public function save($key, $item, $ttl = null)
    {
        $ttl = time() + ($ttl === null ? 86400000 : $ttl);
        return $this->conn->hSet($this->prefix, $key, $ttl.':'.serialize($item)) !== false;
    }

    public function saveDeferred($key, $item, $ttl = null)
    {
        $ttl = time() + ($ttl === null ? 86400000 : $ttl);
        $this->deferreds[$key] = $ttl.':'.serialize($item);
        return true;
    }

    public function commit()
    {
        if (empty($this->deferreds)) {
            return true;
        }
        $ret = $this->conn->hMSet($this->prefix, $this->deferreds); 
        $this->deferreds = [];
        return $ret;
    }

    public function rollback()
    {
        $this->deferreds = [];
        return true;
    }
}
