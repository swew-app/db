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

    private static array $options = [
        PDO::ATTR_EMULATE_PREPARES => 0,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 10,
        PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => 0,
        // PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci',
        // PDO::ATTR_STRINGIFY_FETCHES => true,
    ];

    /**
     * @param  string  $dsn 'pgsql:host=localhost;port=5432;dbname=testdb'
     */
    public static function init(string $dsn, string $username, string $password, ?array $options = null): void
    {
        $options = $options ?? self::$options;
        $pdo = new PDO($dsn, $username, $password, $options);

        self::setPDO($pdo);
    }

    public static function setPDO(PDO $pdo, bool $useConfigOptions = true): bool
    {
        if (! is_null(self::$pdo)) {
            return false;
        }

        if ($useConfigOptions) {
            foreach (self::$options as $attribute => $value) {
                $pdo->setAttribute($attribute, $value);
            }
        }

        self::$pdo = $pdo;

        return true;
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

    public static function getOptions(): array
    {
        return self::$options;
    }

    public static function setOptions(array $options): void
    {
        self::$options = $options;
    }
}
