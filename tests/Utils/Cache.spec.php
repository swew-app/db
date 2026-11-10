<?php

declare(strict_types=1);

use Swew\Db\Utils\Cache;
use Swew\Testing\Model\Stub\MemoryCache;

it('Cache [makeKey]', function () {
    $key = Cache::makeKey('SELECT * FORM [TABLE]', ['id' => 1]);

    expect($key)->toBe('8dfb68cf0ec348e4ea782c1265415874487f72e3');
});

it('Cache [makeCacheItem,getItemValue]', function () {
    $data = ['123', 123];

    $item = Cache::makeCacheItem($data);

    expect($item)->toBe([
        'data' => 'a:2:{i:0;s:3:"123";i:1;i:123;}',
        'expired' => 0,
    ]);

    $res = Cache::getItemValue($item);

    expect($res)->toBe($data);
});

it('Cache [makeCacheItem,isExpired:TRUE]', function () {
    $data = ['123', 123];

    $item = Cache::makeCacheItem($data, -1);
    $res = Cache::isExpired($item);

    expect($res)->toBe(true);
});

it('Cache [makeCacheItem,isExpired:FALSE]', function () {
    $data = ['123', 123];

    $item = Cache::makeCacheItem($data, 10);
    $res = Cache::isExpired($item);

    expect($res)->toBe(false);
});

it('Cache [store,get]', function () {
    $data = ['123', 123];
    $cache = new MemoryCache();
    $key = Cache::makeKey('test');

    $isStored = Cache::store($cache, $key, $data);
    expect($isStored)->toBe(true);

    $res = Cache::get($cache, 'Wrong key');
    expect($res)->toBe(null);

    $res = Cache::get($cache, $key);
    expect($res)->toBe($data);
});

it('Cache [malformed entries are cache misses]', function () {
    $cache = new MemoryCache();
    foreach (['invalid', [], ['data' => 123], ['expired' => 0]] as $item) {
        $cache->set('malformed', $item);
        expect(Cache::get($cache, 'malformed'))->toBe(null);
    }
    expect(fn () => Cache::getItemValue(['data' => 123]))->toThrow(LogicException::class);
});

it('Cache [remember]', function () {
    $data = 'SomeData';
    $cache = new MemoryCache();
    $key = Cache::makeKey('test remember');
    $countOfCall = 0;
    $callback = function () use ($data, &$countOfCall) {
        $countOfCall++;

        return $data;
    };

    expect($countOfCall)->toBe(0);

    $res = Cache::remember($cache, $key, $callback);

    expect($res)->toBe($data);
    expect($countOfCall)->toBe(1);

    // Cached call
    $res = Cache::remember($cache, $key, $callback);
    expect($res)->toBe($data);
    expect($countOfCall)->toBe(1);
});
