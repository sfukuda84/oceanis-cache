<?php
namespace Oceanis\Cache\Pool;

use Oceanis\CachePool;

class File impelements CachePool
{
    protected $dirpath;

    public function __construct($dirpath)
    {
        if (empty($dirpath) || !is_dir($dirpath)) {
            throw new \RuntimeException('Directory not found. path: '.$dirpath);
        }
        $this->dirpath = rtrim($dirpath).'/';
    }

    public function getItem($key)
    {
        $fileTtl = $this->dirpath.$key.'.ttl';
        $fileContent = $this->dirpath.$key.'.cnt';
        if (!is_file($fileTtl)) {
            if (file_exists($fileContent)) {
                unlink($fileContent);
            }
        } else if (!is_file($fileContent)) {
            unlink($fileTtl);
        } else {
            if (($ttl = file_get_contents($fileTtl))
                && $ttl >= time()
                && ($contents = file_get_contents($fileContent))
                && ($contents = unserialize($contents))) {
                return $contents;
            }
            unlink($fileTtl);
            unlink($fileContent);
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
        if (!is_file($fileTtl)) {
            if (file_exists($fileContent)) {
                unlink($fileContent);
            }
        } else if (!is_file($fileContent)) {
            unlink($fileTtl);
        } else {
            if (($ttl = file_get_contents($fileTtl))
                && $ttl >= time()) {
                return true;
            }
            unlink($fileTtl);
            unlink($fileContent);
        }
        return false;
    }

    public function clear()
    {
        $dir = new RegexIterator(
            new DirectoryIterator($this->dirpath),
            '/\.(:?ttl|cnt)$/',
            RegexIterator::GET_MATCH
        );
        foreach ($dir as $fileinfo) {
            if (!unlink($fileinfo)) {
                return false;
            }
        }
        return true;
    }

    public function deleteItem($key)
    {
        $ret = true;
        if (file_exists($this->dirpath.$key.'.ttl')) {
            unlink($this->dirpath.$key.'.ttl') || $ret = false;
        }
        if (file_exists($this->dirpath.$key.'.cnt')) {
            unlink($this->dirpath.$key.'.cnt') || $ret = false;
        }
        return $ret;
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
