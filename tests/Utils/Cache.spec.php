<?php

declare(strict_types=1);

use Swew\Db\Utils\Cache;
use Swew\Testing\Model\Stub\MemoryCache;

it('Cache [makeKey]', function () {
    $key = Cache::makeKey('SELECT * FORM [TABLE]', ['id' => 1]);

    expect($key)->toBe('8d/fb68cf0ec348e4ea782c1265415874487f72e3');
});

it('Cache [makeCacheItem,getItemValue]', function () {
    $data= ['123', 123];

    $item = Cache::makeCacheItem($data);

    expect($item)->toBe([
        'data' => 'a:2:{i:0;s:3:"123";i:1;i:123;}',
        'expired' => 0,
    ]);

    $res = Cache::getItemValue($item);

    expect($res)->toBe($data);
});

it('Cache [makeCacheItem,isExpired:TRUE]', function () {
    $data= ['123', 123];

    $item = Cache::makeCacheItem($data, -1);
    $res = Cache::isExpired($item);

    expect($res)->toBe(true);
});

it('Cache [makeCacheItem,isExpired:FALSE]', function () {
    $data= ['123', 123];

    $item = Cache::makeCacheItem($data, 10);
    $res = Cache::isExpired($item);

    expect($res)->toBe(false);
});

it('Cache [store,get]', function () {
    $data= ['123', 123];

    $cache = new MemoryCache();
    $key = Cache::makeKey('test');

    $isStored = Cache::store($cache, $key, $data);
    expect($isStored)->toBe(true);

    $res = Cache::get($cache, 'Wrong key');
    expect($res)->toBe(null);

    $res = Cache::get($cache, $key);
    expect($res)->toBe($data);
});
