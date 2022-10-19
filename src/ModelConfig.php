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
        if (self::$pdo && self::$pdo !== $pdo) {
            throw new LogicException('PDO already exists');
        }
        self::$pdo = $pdo;
    }

    public static function getPDO(): PDO
    {
        if (is_null(self::$pdo)) {
            throw new LogicException('Please set PDO, use method ModelConfig::setPDO');
        }

        return self::$pdo;
    }

    public static function removePDO(): void
    {
        self::$pdo = null;
    }

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

    private static string $tablePrefix = '';

    public static function setTablePrefix(string $tablePrefix): void
    {
        self::$tablePrefix = $tablePrefix;
    }

    public static function getTablePrefix(): string
    {
        return self::$tablePrefix;
    }
}
