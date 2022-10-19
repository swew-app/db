<?php

declare(strict_types=1);

namespace Swew\Db;

use PDO;
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

    public static function up(callable $callback): void
    {
        self::$upCallbackList[] = $callback;
    }

    public static function down(callable $callback): void
    {
        self::$downCallbackList[] = $callback;
    }

    /**
     * [x] находим файлы миграции по шаблону
     * [x] получаемданный из таблицы миграций, если такой нет, то создаем ее
     * [x] фильтруем названия файлов, относительно уже совершенных миграций сохраненных в БД
     * [x] проходим по файлам и выбираем UP/DOWN колбеки в очередь
     * [ ] создаем строку запись для совершении миграции в БД и добавляем ее в очередь
     * [ ] делаем транзакцию на совершение миграций
     * [ ] создаем запись в таблице миграций
     */
    public static function run(string $filePattern, bool $isUp, PDO $pdo): void
    {
        self::$migratedFileNames = [];

        self::searchFiles($filePattern);

        self::loadMigrationsStatistic();

        $migratedFileNames = self::$migratedFileNames;

        self::$migrationFiles = array_filter(
            self::$migrationFiles,
            function (string $path) use ($migratedFileNames) {
                return in_array(basename($path), $migratedFileNames) !== true;
            }
        );

        self::loadCallbacks();

        $list = $isUp ? self::$upCallbackList : self::$downCallbackList;

        $isDone = self::migrate($list);

        if ($isDone) {
            self::updateMigrationTable();
        }
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
        });
    }

    private static function updateMigrationTable(): void
    {
        $batch = self::$currentBatch;

        $list = array_map(function (string $fileName) use ($batch) {
            return [
                'migration_file' => $fileName,
                'batch' => $batch,
            ];
        }, self::$migrationFiles);

        MigrationModel::vm()->insertMany($list);
    }
}
