<?php

declare(strict_types=1);

namespace Swew\Testing\Model\Stub;

use DateInterval;
use DateTime;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Traversable;

/**
 * The Memory cache just stores everything in PHP memory. This cache is gone
 * once the process ends.
 *
 * This is useful as a test-double or for long-running processes that just need
 * a local fast cache.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (https://evertpot.com/)
 * @license http://sabre.io/license/
 */
class MemoryCache implements CacheInterface
{
    public array $cache = [];

    /**
     * Fetches a value from the cache.
     *
     * @param  string  $key     the unique key of this item in the cache
     * @param  mixed  $default default value to return if the key does not exist
     * @return mixed the value of the item from the cache, or $default in case of cache miss
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *                                                   MUST be thrown if the $key string is not a legal value
     */
    public function get($key, $default = null): mixed
    {
        if (! is_string($key)) {
            throw new InvalidArgumentException('$key must be a string');
        }
        if (! isset($this->cache[$key]) || $this->isExpired($key)) {
            return $default;
        }

        return $this->cache[$key][1];
    }

    /**
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    private function isExpired(string $key): bool
    {
        $expire = $this->cache[$key][0];
        if (null !== $expire && $expire < time()) {
            // If a ttl was set and it expired in the past, invalidate the cache.
            $this->delete($key);

            return true;
        }

        return false;
    }

    /**
     * Persists data in the cache, uniquely referenced by a key with an
     * optional expiration TTL time.
     *
     * @param  string  $key   the key of the item to store
     * @param  mixed  $value the value of the item to store, must
     *                                     be serializable
     * @param  int|DateInterval|null  $ttl   Optional. The TTL value of this item.
     *                                     If no value is sent and the driver
     *                                     supports TTL then the library may set
     *                                     a default value for it or let the
     *                                     driver take care of that.
     * @return bool true on success and false on failure
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *                                                   MUST be thrown if the $key string is not a legal value
     */
    public function set($key, $value, $ttl = null): bool
    {
        if (! is_string($key)) {
            throw new InvalidArgumentException('$key must be a string');
        }

        $expire = null;
        if (isset($ttl)) {
            if ($ttl instanceof DateInterval) {
                $expire = (new DateTime('now'))->add($ttl)->getTimeStamp();
            } elseif (is_int($ttl) || ctype_digit((string) $ttl)) {
                $expire = time() + $ttl;
            }
        }
        $this->cache[$key] = [$expire, $value];

        return true;
    }

    /**
     * Delete an item from the cache by its unique key.
     *
     * @param  string  $key the unique cache key of the item to delete
     * @return bool True if the item was successfully removed. False if there was an error.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *                                                   MUST be thrown if the $key string is not a legal value
     */
    public function delete($key): bool
    {
        if (! is_string($key)) {
            throw new InvalidArgumentException('$key must be a string');
        }
        unset($this->cache[$key]);

        return true;
    }

    /**
     * Wipes clean the entire cache's keys.
     *
     * @return bool true on success and false on failure
     */
    public function clear(): bool
    {
        $this->cache = [];

        return true;
    }

    /**
     * Determines whether an item is present in the cache.
     *
     * NOTE: It is recommended that has() is only to be used for cache warming
     * type purposes and not to be used within your live applications operations
     * for get/set, as this method is subject to a race condition where your
     * has() will return true and immediately after, another script can remove
     * it making the state of your app out of date.
     *
     * @param  string  $key the cache item key
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *                                                   MUST be thrown if the $key string is not a legal value
     */
    public function has($key): bool
    {
        if (! is_string($key)) {
            throw new InvalidArgumentException('$key must be a string');
        }

        return isset($this->cache[$key]) && ! $this->isExpired($key);
    }

    /**
     * Obtains multiple cache items by their unique keys.
     *
     * This particular implementation returns its result as a generator.
     *
     * @param  iterable  $keys    a list of keys that can obtained in a single
     *                          operation
     * @param  mixed  $default default value to return for keys that do not
     *                          exist
     * @return iterable A list of key => value pairs. Cache keys that do not
     *                  exist or are stale will have $default as value.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *                                                   MUST be thrown if $keys is neither an array nor a Traversable,
     *                                                   or if any of the $keys are not a legal value
     */
    public function getMultiple($keys, $default = null): iterable
    {
        if (! is_array($keys) && ! $keys instanceof Traversable) {
            throw new InvalidArgumentException('$keys must be traversable');
        }

        foreach ($keys as $key) {
            yield $key => $this->get($key, $default);
        }
    }

    /**
     * Persists a set of key => value pairs in the cache, with an optional TTL.
     *
     * @param  iterable  $values a list of key => value pairs for a
     *                                      multiple-set operation
     * @param  int|DateInterval|null  $ttl    Optional. The TTL value of this
     *                                      item. If no value is sent and the
     *                                      driver supports TTL then the library
     *                                      may set a default value for it or
     *                                      let the driver take care of that.
     * @return bool true on success and false on failure
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *                                                   MUST be thrown if $values is neither an array nor a Traversable,
     *                                                   or if any of the $values are not a legal value
     */
    public function setMultiple($values, $ttl = null): bool
    {
        if (! is_array($values) && ! $values instanceof Traversable) {
            throw new InvalidArgumentException('$values must be traversable');
        }

        $result = true;
        foreach ($values as $key => $value) {
            if (! $this->set($key, $value, $ttl)) {
                $result = false;
            }
        }

        return $result;
    }

    /**
     * Deletes multiple cache items in a single operation.
     *
     * @param  iterable  $keys a list of string-based keys to be deleted
     * @return bool True if the items were successfully removed. False if there
     *              was an error.
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     *                                                   MUST be thrown if $keys is neither an array nor a Traversable,
     *                                                   or if any of the $keys are not a legal value
     */
    public function deleteMultiple($keys): bool
    {
        if (! is_array($keys) && ! $keys instanceof Traversable) {
            throw new InvalidArgumentException('$keys must be traversable');
        }

        $result = true;
        foreach ($keys as $key) {
            if (! $this->delete($key)) {
                $result = false;
            }
        }

        return $result;
    }
}
