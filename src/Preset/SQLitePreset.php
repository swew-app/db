<?php

declare(strict_types=1);

namespace Swew\Db\Preset;

class SQLitePreset
{
    const SPEED = 1;
    const MEMORY = 2;

    public function usePreset(\PDO $pdo, int $attr): void
    {
        if ($attr === SQLitePreset::MEMORY) {
            // Оптимизация производительности SQLite
            $pdo->exec('PRAGMA journal_mode = MEMORY;'); // Используем оперативную память для журнала
            $pdo->exec('PRAGMA synchronous = OFF;'); // Отключаем синхронизацию (рискованно, но быстро)
            $pdo->exec('PRAGMA temp_store = MEMORY;'); // Используем оперативную память для временных таблиц
            $pdo->exec('PRAGMA cache_size = 100000;'); // Увеличиваем размер кэша
            $pdo->exec('PRAGMA foreign_keys = ON;'); // Включаем поддержку внешних ключей (если необходимо)
        }

        if ($attr === SQLitePreset::SPEED) {
            // Apply optimizations
            $pdo->exec('PRAGMA journal_mode = WAL'); // Write-Ahead Logging - allows concurrent reads during writes
            $pdo->exec('PRAGMA synchronous = NORMAL'); // Reduce fsync calls
            $pdo->exec('PRAGMA cache_size = -50000'); // ~50MB RAM cache // Increase cache size
            $pdo->exec('PRAGMA query_only = 1'); // For read-only connections // Optimize queries
        }
    }
}
