<?php

declare(strict_types=1);

namespace Swew\Db;

use LogicException;
use PDO;

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
}
