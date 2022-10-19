<?php

declare(strict_types=1);

namespace Swew\Db;

use Swew\Db\Parts\MigrationModel;
use Swew\Db\Utils\Files;

class Migrate
{
    private static array $migrationFiles = [];

    private static array $upCallbackList = [];

    private static array $downCallbackList = [];

    private static array $migratedFileNames = [];

    private static int $currentBatch = 0;

    private function __construct()
    {
    }

    private static function reset(): void
    {
        self::$migrationFiles = [];
        self::$upCallbackList = [];
        self::$downCallbackList = [];
        self::$migratedFileNames = [];
        self::$currentBatch = 0;
    }

    public static function up(callable $callback): void
    {
        self::$upCallbackList[] = $callback;
    }

    public static function down(callable $callback): void
    {
        self::$downCallbackList[] = $callback;
    }

    /**
     * [x] find migration files by pattern
     * [x] retrieve data from the migration table, if it does not exist, we create it
     * [x] filter file names relative to the migrations already made in the database
     * [x] go through files and select UP/DOWN columns in the queue
     * [x] create row entry for migration in database and add it to queue
     * [x] make a transaction for the migration
     * [x] create an entry in the migration table
     */
    public static function run(string $filePattern, bool $isUp, int $batchSteps = 0): bool
    {
        self::reset();

        self::searchFiles($filePattern);

        self::loadMigrationsStatistic();

        // up
        if ($isUp) {
            return self::runUp();
        }
        // down
        return self::runDown($batchSteps);
    }

    public static function getMigratedFiles(): array
    {
        return array_map('basename', self::$migrationFiles);
    }

    private static function searchFiles(string $filePattern): void
    {
        $filesList = Files::getFilesByPattern($filePattern);

        foreach ($filesList as $filePath) {
            self::$migrationFiles[] = $filePath;
        }
    }

    private static function loadMigrationsStatistic(): void
    {
        // проверяем есть ли таблица, миграций, если нет, то создаем
        if (! MigrationModel::vm()->isTableExists()) {
            $table = new Migrator(MigrationModel::vm()->getDriverType());

            $table->tableCreate(MigrationModel::vm()->getTableName());
            $table->id();
            $table->string('migration_file');
            $table->integer('batch');
            $table->timestamps();

            MigrationModel::vm()->query($table->getSql())->exec();
        }

        self::$currentBatch = MigrationModel::vm()->max('batch')->getValue('batch') ?: 0;
        self::$currentBatch += 1;

        $data = MigrationModel::vm()->select('migration_file')->get() ?: [];
        self::$migratedFileNames = is_array($data) ? $data : [];

        foreach (self::$migratedFileNames as &$value) {
            $value = $value['migration_file'];
        }
    }

    private static function loadCallbacks(): void
    {
        foreach (self::$migrationFiles as $fileName) {
            include $fileName;
        }
    }

    private static function migrate(array $callbacks): bool
    {
        $queries = [];

        foreach ($callbacks as $callback) {
            $migrator = new Migrator(MigrationModel::vm()->getDriverType());

            $callback($migrator);

            $queries[] = $migrator->getSql();
        }

        return MigrationModel::transaction(function () use ($queries) {
            foreach ($queries as $query) {
                MigrationModel::vm()->query($query)->exec();
            }
        }, true);
    }

    private static function runUp(): bool
    {
        $migratedFileNames = self::$migratedFileNames;

        self::$migrationFiles = array_filter(
            self::$migrationFiles,
            function (string $path) use ($migratedFileNames) {
                return in_array(basename($path), $migratedFileNames) !== true;
            }
        );

        self::loadCallbacks();

        $isDone = self::migrate(self::$upCallbackList);

        if ($isDone) {
            self::updateMigrationTable();
        }

        return $isDone;
    }

    private static function runDown(int $batchSteps = 0): bool
    {
        $steps = [];

        if ($batchSteps > 0) {
            $maxStep = MigrationModel::vm()->max('batch')->getValue('batch');

            while ($batchSteps > 0 && $maxStep > 0) {
                $steps[] = $maxStep;
                $batchSteps--;
                $maxStep--;
            }

            $migratedFileNames = MigrationModel::vm()
                ->select('migration_file')
                ->whereIn('batch', $steps)
                ->orderBy('batch', 'DESC')
                ->getMap(
                    fn (array $v) => $v['migration_file']
                );
        } else {
            $migratedFileNames = MigrationModel::vm()
                ->select('migration_file')
                ->orderBy('batch', 'DESC')
                ->getMap(
                    fn (array $v) => $v['migration_file']
                );
        }

        if (is_bool($migratedFileNames)) {
            return false;
        }

        self::$migrationFiles = array_filter(
            self::$migrationFiles,
            function (string $path) use ($migratedFileNames) {
                return in_array(basename($path), $migratedFileNames);
            }
        );

        self::loadCallbacks();

        $isDone = self::migrate(self::$downCallbackList);

        if ($isDone) {
            if (count($steps) > 0) {
                MigrationModel::vm()->delete()->whereIn('batch', $steps)->exec();
            } else {
                MigrationModel::vm()->delete()->exec();
            }
        }

        return $isDone;
    }

    private static function updateMigrationTable(): void
    {
        $batch = self::$currentBatch;

        $list = array_map(function (string $fileName) use ($batch) {
            return [
                'migration_file' => basename($fileName),
                'batch' => $batch,
            ];
        }, self::$migrationFiles);

        MigrationModel::vm()->insertMany($list);
    }
}
