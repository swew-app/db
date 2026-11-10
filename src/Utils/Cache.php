<?php

declare(strict_types=1);

namespace Swew\Db\Utils;

use LogicException;
use Psr\SimpleCache\CacheInterface;

class Cache
{
    private function __construct() {}

    public static function makeKey(mixed ...$args): string
    {
        $key = serialize($args);

        return sha1($key);
    }

    /** @return array{data: string, expired: int} */
    public static function makeCacheItem(mixed $value, int $seconds = 0): array
    {
        if ($seconds !== 0) {
            $seconds = time() + $seconds;
        }

        return [
            'data' => serialize($value),
            'expired' => $seconds,
        ];
    }

    public static function getItemValue(array $item): mixed
    {
        if (!isset($item['data']) || !is_string($item['data'])) {
            throw new LogicException('Invalid serialized cache data');
        }

        return unserialize($item['data']);
    }

    public static function isExpired(array $item): bool
    {
        if (!array_key_exists('data', $item)) {
            return false;
        }

        if (!array_key_exists('expired', $item)) {
            return false;
        }

        if (!is_int($item['expired'])) {
            return false;
        }

        if ($item['expired'] === 0) {
            return false;
        }

        if ($item['expired'] > time()) {
            return false;
        }

        return true;
    }

    public static function store(CacheInterface $cache, string $key, mixed $data, int $seconds = 0): bool
    {
        $item = self::makeCacheItem($data, $seconds);

        return $cache->set($key, $item);
    }

    public static function get(CacheInterface $cache, string $key): mixed
    {
        $item = $cache->get($key);

        if (!is_array($item) || !isset($item['data']) || !is_string($item['data']) || self::isExpired($item)) {
            return null;
        }

        return self::getItemValue($item);
    }

    public static function remember(CacheInterface $cache, string $key, callable $callback, int $seconds = 0): mixed
    {
        if ($key === '') {
            throw new LogicException('Passed empty key in remember function');
        }

        $data = self::get($cache, $key);

        if (!is_null($data)) {
            return $data;
        }

        $data = $callback();

        if (is_null($data)) {
            return $data;
        }

        self::store($cache, $key, $data, $seconds);

        return $data;
    }
}
