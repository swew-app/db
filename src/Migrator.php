<?php

declare(strict_types=1);

namespace Swew\Db;

use InvalidArgumentException;
use PDO;
use RuntimeException;
use Throwable;

/**
 * Keep the migration lifecycle together rather than splitting it solely for aggregate metrics.
 * @mago-expect lint:cyclomatic-complexity
 * @mago-expect lint:kan-defect
 */
final class Migrator
{
    private PDO $pdo;

    public function __construct(
        private string $directory,
        ?PDO $pdo = null,
    ) {
        $this->pdo = $pdo ?? ModelConfig::getPDO();
        if (!in_array($this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME), ['sqlite', 'pgsql', 'mysql'], true)) {
            throw new InvalidArgumentException('Migrations support SQLite, PostgreSQL, and MySQL.');
        }
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public static function create(string $directory, string $name): string
    {
        if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/D', $name)) {
            throw new InvalidArgumentException('Migration names must contain only letters, digits, and underscores.');
        }
        if (!is_dir($directory) && !mkdir($directory, 0755, true)) {
            throw new RuntimeException("Cannot create migration directory: {$directory}");
        }
        $version = date('YmdHis');
        if (glob($directory . '/' . $version . '_*.sql') !== []) {
            throw new RuntimeException('A migration already exists for this second; retry in one second.');
        }
        $path = $directory . '/' . $version . '_' . $name . '.sql';
        $file = fopen($path, 'x');
        if ($file === false) {
            throw new RuntimeException("Cannot create migration: {$path}");
        }
        try {
            $template = "-- +swew Up\n\n-- +swew Down\n";
            if (fwrite($file, $template) !== strlen($template)) {
                throw new RuntimeException("Cannot write migration: {$path}");
            }
        } finally {
            fclose($file);
        }

        return $path;
    }

    /** @return list<array{version: int, name: string, applied: bool}> */
    public function status(): array
    {
        $files = $this->files();
        $applied = $this->applied();
        foreach ($applied as $version => $name) {
            if (!isset($files[$version]) || basename($files[$version]) !== $name) {
                throw new RuntimeException("Applied migration is missing or renamed: {$name}");
            }
        }
        $status = [];
        foreach ($files as $version => $path) {
            $status[] = ['version' => $version, 'name' => basename($path), 'applied' => isset($applied[$version])];
        }

        return $status;
    }

    /** Apply all pending migrations. @return list<int> */
    public function up(): array
    {
        $status = $this->status();
        $latest = 0;
        foreach ($status as $migration) {
            if ($migration['applied']) {
                $latest = $migration['version'];
            }
        }
        foreach ($status as $migration) {
            if (!$migration['applied'] && $migration['version'] < $latest) {
                throw new RuntimeException("Out-of-order migration: {$migration['name']}");
            }
        }
        $versions = [];
        foreach ($status as $migration) {
            if (!$migration['applied']) {
                $this->run($migration, true);
                $versions[] = $migration['version'];
            }
        }

        return $versions;
    }

    /** Roll back the latest applied migration, or return null if none exist. */
    public function down(): ?int
    {
        foreach (array_reverse($this->status()) as $migration) {
            if ($migration['applied']) {
                $this->run($migration, false);

                return $migration['version'];
            }
        }

        return null;
    }

    /** @return array<int, string> */
    private function files(): array
    {
        if (!is_dir($this->directory) || !is_readable($this->directory)) {
            throw new RuntimeException("Cannot read migration directory: {$this->directory}");
        }
        $paths = glob($this->directory . '/*.sql');
        if ($paths === false) {
            throw new RuntimeException('Cannot list migrations.');
        }
        $files = [];
        foreach ($paths as $path) {
            if (!preg_match('/^([0-9]+)_[a-zA-Z0-9_]+\.sql$/D', basename($path), $match)) {
                throw new RuntimeException("Invalid migration filename: {$path}");
            }
            $version = filter_var(ltrim($match[1], '0'), FILTER_VALIDATE_INT);
            if ($version === false || $version < 1 || isset($files[$version])) {
                throw new RuntimeException("Invalid or duplicate migration version: {$path}");
            }
            $files[$version] = $path;
        }
        ksort($files, SORT_NUMERIC);

        return $files;
    }

    /** @return array<int, string> */
    private function applied(): array
    {
        if ($this->pdo->inTransaction()) {
            throw new RuntimeException('Run migrations outside an existing transaction.');
        }
        $this->pdo->exec('CREATE TABLE IF NOT EXISTS swew_migrations (
            version BIGINT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        )');
        $statement = $this->pdo->query('SELECT version, name FROM swew_migrations');
        assert($statement !== false);
        /** @var array<int|string, string> $rows */
        $rows = $statement->fetchAll(PDO::FETCH_KEY_PAIR);
        $applied = [];
        foreach ($rows as $version => $name) {
            $applied[(int) $version] = $name;
        }

        return $applied;
    }

    /** @return array{up: string, down: string, transaction: bool} */
    private function parse(string $path): array
    {
        $lines = file($path);
        if ($lines === false) {
            throw new RuntimeException("Cannot read migration: {$path}");
        }
        $sql = ['up' => '', 'down' => '', 'transaction' => true];
        $section = null;
        $seen = [];
        $block = false;
        foreach ($lines as $line) {
            if (preg_match('/^\s*--\s*\+swew\s+(.+?)\s*$/', $line, $match)) {
                $directive = $match[1];
                if ($directive === 'NO TRANSACTION' && $section === null) {
                    $sql['transaction'] = false;
                } elseif (($directive === 'Up' || $directive === 'Down') && !$block) {
                    $next = $directive === 'Up' ? 'up' : 'down';
                    if (
                        isset($seen[$next])
                        || $next === 'up' && $section !== null
                        || $next === 'down' && $section !== 'up'
                    ) {
                        throw new RuntimeException("Invalid Up/Down order: {$path}");
                    }
                    $section = $next;
                    $seen[$next] = true;
                } elseif ($directive === 'StatementBegin' && $section !== null && !$block) {
                    $block = true;
                } elseif ($directive === 'StatementEnd' && $block) {
                    $block = false;
                } else {
                    throw new RuntimeException("Invalid or unsupported swew directive: {$directive} in {$path}");
                }
                continue;
            }
            if ($section === 'up') {
                $sql['up'] .= $line;
            } elseif ($section === 'down') {
                $sql['down'] .= $line;
            } elseif (trim($line) !== '' && !str_starts_with(ltrim($line), '--')) {
                throw new RuntimeException("SQL before Up annotation: {$path}");
            }
        }
        if (!isset($seen['up'], $seen['down']) || $block) {
            throw new RuntimeException("Migration requires Up/Down and balanced statement annotations: {$path}");
        }

        return $sql;
    }

    /** @param array{version: int, name: string, applied: bool} $migration */
    private function run(array $migration, bool $up): void
    {
        $sql = $this->parse($this->directory . '/' . $migration['name']);
        // MySQL DDL implicitly commits; use explicit recovery there if a migration fails.
        $transaction = $sql['transaction'] && $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) !== 'mysql';
        if ($transaction) {
            $this->pdo->beginTransaction();
        }
        try {
            $body = $sql[$up ? 'up' : 'down'];
            if (trim($body) !== '') {
                $this->pdo->exec($body);
            }
            $statement = $this->pdo->prepare(
                $up
                    ? 'INSERT INTO swew_migrations (version, name) VALUES (?, ?)'
                    : 'DELETE FROM swew_migrations WHERE version = ?',
            );
            assert($statement !== false);
            $statement->execute($up ? [$migration['version'], $migration['name']] : [$migration['version']]);
            if ($transaction) {
                $this->pdo->commit();
            }
        } catch (Throwable $error) {
            if ($transaction && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw new RuntimeException("Migration {$migration['name']} failed: {$error->getMessage()}", 0, $error);
        }
    }
}
