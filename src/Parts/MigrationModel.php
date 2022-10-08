<?php

declare(strict_types=1);

namespace Swew\Db\Parts;

use Swew\Db\Model;

final class MigrationModel extends Model
{
    public string $migration_file = '';

    public int $batch = 1;

    public function table(): string
    {
        return 'migrations';
    }

    protected function hasTimestamp(): bool
    {
        return true;
    }

    // SQL
    public function isTableExists(): bool
    {
        $sql = match ($this->getDriverType()) {
            'mysql' => "SHOW TABLES LIKE '[TABLE]'",
            'pgsql' => "SELECT EXISTS (SELECT FROM pg_tables WHERE schemaname = 'public' AND tablename = '[TABLE]')",
            'sqlite' => "SELECT name FROM sqlite_master WHERE type='table' AND name='[TABLE]'",
        };

        $res = $this->query($sql)->getValue();

        return !!$res;
    }

    public const CREATE_TABLE = <<<QUERY
    CREATE TABLE IF NOT EXISTS [TABLE] (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        migration_file VARCHAR(255) NOT NULL,
        batch INTEGER,
        created_at TEXT,
        updated_at TEXT
    )
    QUERY;
}
