<?php
namespace Oceanis\Cache\Pool;

use Oceanis\Cache\CachePool;

class File implements CachePool
{

    public function __construct($dirpath)
    {
        if (!is_dir($dirpath)) {
            throw new \RuntimeException('Directory not found. path: '.$dirpath);
        } 
        $this->dirpath = rtrim($dirpath).'/';
    }

    public function getItem($key)
    {
        $filepath = $this->dirpath.$key.'.cache';
        if (is_file($filepath)) {
            if (($contents = unserialize(file_get_contents($filepath)))
                && !empty($contents['ttl'])
                && !empty($contents['item'])
                && $contents['ttl'] >= time()) {
                return $contents['item'];
            } else {
                unlink($filepath);
            }
        }
        return null;
    }

    public function getItems(array $keys = [])
    {
        $ret = [];
        foreach ($keys as $key) {
            if ($item = $this->getItem($key)) {
                $ret[$key] = $item;
            }
        }
        return $ret;
    }

    public function hasItem($key)
    {
        return $this->getItem($key) !== null;
    }

    public function clear()
    {
        $ret = true;
        $dir = new \DirectoryIterator($this->dirpath);
        foreach ($dir as $fileinfo) {
            $pathname = $fileinfo->getPathname();
            if (preg_match('/\.cache$/', $pathname) && !unlink($pathname)) {
                $ret = false;
            }
        }
        return $ret;
    }

    public function deleteItem($key)
    {
        $filepath = $this->dirpath.$key.'.cache';
        return !file_exists($filepath) || unlink($filepath);
    }

    public function deleteItems(array $keys)
    {
        $ret = true;
        foreach ($keys as $key) {
            if (!$this->deleteItem($key)) {
                $ret = false;
            }
        }
        return $ret;
    }

    public function save($key, $item, $ttl = null)
    {
        $ttl = time() + ($ttl ?? 86400000);
        return file_put_contents($this->dirpath.$key.'.cache', serialize(compact('ttl', 'item')), LOCK_EX) !== false;
    }

    public function saveDeferred($key, $item, $ttl = null)
    {
        $ttl = time() + ($ttl ?? 86400000);
        $this->deferreds[$key] = serialize(compact('ttl', 'item'));
        return true;
    }

    public function commit()
    {
        $ret = true;
        foreach ($this->deferreds as $key => $item) {
            if (!file_put_contents($this->dirpath.$key.'.cache', $item, LOCK_EX)) {
                $ret = false;
            }
        }
        return $ret;
    }

    public function rollback()
    {
        $this->deferreds = [];
        return true;
    }
}
