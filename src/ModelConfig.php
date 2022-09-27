<?php

declare(strict_types=1);

namespace Swew\Db;

use LogicException;
use PDO;
use Psr\SimpleCache\CacheInterface;

final class ModelConfig
{
    private function __construct()
    {
    }

    private static ?PDO $pdo = null;

    public static function setPDO(PDO $pdo): void
    {
        if (self::$pdo) {
            throw new LogicException('PDO already exists');
        }
        self::$pdo = $pdo;
    }

    public static function getPDO(): PDO|null
    {
        return self::$pdo;
    }

    private static ?CacheInterface $cache = null;

    public static function setCache(CacheInterface $cache): void
    {
        if (self::$cache) {
            throw new LogicException('Cache already exists');
        }
        self::$cache = $cache;
    }

    public static function getCache(): CacheInterface
    {
        if (is_null(self::$cache)) {
            throw new LogicException('Cache is not exists');
        }

        return self::$cache;
    }
}
