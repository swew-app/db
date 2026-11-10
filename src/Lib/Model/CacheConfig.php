<?php

declare(strict_types=1);

namespace Swew\Db\Lib\Model;

use LogicException;
use Psr\SimpleCache\CacheInterface;

trait CacheConfig
{
    private static ?CacheInterface $defaultCache = null;

    public static function hasDefaultCache(): bool
    {
        return (bool) self::$defaultCache;
    }

    public static function setDefaultCache(CacheInterface $defaultCache): void
    {
        if (self::$defaultCache) {
            throw new LogicException('Cache already exists');
        }
        self::$defaultCache = $defaultCache;
    }

    public static function getDefaultCache(): CacheInterface
    {
        if (is_null(self::$defaultCache)) {
            throw new LogicException('Cache is not exists');
        }

        return self::$defaultCache;
    }
}
