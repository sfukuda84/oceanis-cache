<?php
require __DIR__.'/vendor/autoload.php';

class CacheFactory {
    const TYPE_APCU = 'Apcu'; 
    const TYPE_REDIS = 'Redis';

    const TYPES = [
        self::TYPE_APCU,
        self::TYPE_REDIS
    ];

    private static $INSTANCE;

    public static function instance()
    {
        if (!isset(self::$INSTANCE)) {
            self::$INSTANCE = new self; 
        }
        return self::$INSTANCE;
    }

    private function __construct()
    {
        $redis = new Redis();
        $redis->connect('localhost');
        $this->caches = [
            self::TYPE_APCU => new Oceanis\Cache\Pool\Apcu(),
            self::TYPE_REDIS => new Oceanis\Cache\Pool\Redis($redis)
        ];
    }

    public function factory($type)
    {
        if (empty($this->caches[$type])) {
            throw new Exception('Invalid type.');
        }
        return $this->caches[$type];
    }
}

$factory = CacheFactory::instance();
foreach (CacheFactory::TYPES as $type) {
    $cache = $factory->factory($type);
    echo 'cache type: '.$type, PHP_EOL;
    assert($cache->save('foo1', 'bar1', 100) === true);
    assert($cache->getItem('foo1') === 'bar1');
    assert($cache->hasItem('foo1') === true);
    assert($cache->hasItem('foo9') === false);
    assert($cache->save('foo1', 'bar1', 1) === true);
    assert($cache->hasItem('foo1') === true);
    assert($cache->getItem('foo1') === 'bar1');
    assert($cache->save('foo2', 'bar2', 200) === true);
    assert($cache->getItems(['foo1', 'foo2']) === ['foo1' => 'bar1', 'foo2' => 'bar2']);
    assert($cache->deleteItem('foo1') === true);
    assert($cache->deleteItem('foo1') === true);
    assert($cache->getItem('foo1') === null);
    assert($cache->save('foo3', 'bar3', 300) === true);
    assert($cache->save('foo4', 'bar4', 400) === true);
    assert($cache->deleteItems(['foo1', 'foo2', 'foo3']) === true);
    assert($cache->getItems(['foo1', 'foo2', 'foo3', 'foo4']) === ['foo4' => 'bar4']);
    assert($cache->saveDeferred('foo1', 'bar1') === true);
    assert($cache->saveDeferred('foo2', 'bar2') === true);
    assert($cache->saveDeferred('foo3', 'bar3') === true);
    assert($cache->getItems(['foo1', 'foo2', 'foo3', 'foo4']) === ['foo4' => 'bar4']);
    assert($cache->commit() === true);
    assert($cache->getItems(['foo1', 'foo2', 'foo3', 'foo4']) === [
        'foo1' => 'bar1',
        'foo2' => 'bar2',
        'foo3' => 'bar3',
        'foo4' => 'bar4'
    ]);
    assert($cache->saveDeferred('foo4', 'bar4') === true);
    assert($cache->saveDeferred('foo5', 'bar5') === true);
    assert($cache->rollback() === true);
    assert($cache->commit() === true);
    assert($cache->getItems(['foo1', 'foo2', 'foo3', 'foo4']) === [
        'foo1' => 'bar1',
        'foo2' => 'bar2',
        'foo3' => 'bar3',
        'foo4' => 'bar4'
    ]);
    assert($cache->clear());
    assert($cache->getItems(['foo1', 'foo2', 'foo3', 'foo4']) === []);
}
