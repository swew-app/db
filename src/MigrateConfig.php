<?php

declare(strict_types=1);

namespace Swew\Db;

use PDO;

final class MigrateConfig
{
    public string $filePattern = '';

    public string $migrationTableName = 'migrations';

    public bool $isUpMigration = true;

    public ?PDO $pdo = null;

    public function getDoneMigrationKeys(): array
    {
        return [];
    }
}
