<?php

declare(strict_types=1);

namespace Swew\Db;

use PDO;
use Swew\Db\Migrator;
use Swew\Db\Utils\Files;
use Swew\Db\Parts\MigrationModel;

class Migrate
{
    private static array $filesListWithCallbacks = [];

    private static array $callbackList = [];

    private function __construct()
    {
    }

    public static function up(callable $callback): void
    {
        self::$callbackList[] = $callback;
    }

    public static function down(callable $callback): void
    {
        self::$callbackList[] = $callback;
    }

    /**
     * [x] находим файлы миграции по шаблону
     * [ ] получаемданный из таблицы миграций, если такой нет, то создаем ее
     * [ ] фильтруем названия файлов, относительно уже совершенных миграций сохраненных в БД
     * [ ] проходим по файлам и выбираем UP/DOWN колбеки в очередь
     * [ ] создаем строку запись для совершении миграции в БД и добавляем ее в очередь
     * [ ] делаем транзакцию на совершение миграций
     */
    public static function run(string $filePattern, bool $isUp, PDO $pdo): void
    {
        self::searchFiles($filePattern);

        self::loadMigrationsStatistic();

        // $migrator = new Migrator();
        // $callback($migrator);
    }

    public static function searchFiles(string $filePattern)
    {
        self::loadMigrationsStatistic();

        $filesList = Files::getFilesByPattern($filePattern);

        foreach ($filesList as $filePath) {
            self::$filesListWithCallbacks[$filePath] = [];
        }
    }

    public static function loadMigrationsStatistic()
    {
        // проверяем есть ли таблица, миграций, если нет, то создаем
        if (!MigrationModel::vm()->isTableExists()) {
            $table = new Migrator(MigrationModel::vm()->getDriverType());

            $table->tableCreate(MigrationModel::vm()->getTableName());
            $table->id();
            $table->string('migration_file');
            $table->integer('batch');
            $table->timestamp();

            MigrationModel::vm()->query($table->getSql())->exec();
        }

        $migration = new MigrationModel();
        $migration->migration_file = '218_file_name.php';
        $migration->batch = 1;
        $migration->save();

        // $migrationFiles = MigrationModel::vm()->select('migration_file')->get();
        $migrationFiles = MigrationModel::vm()->select()->get();
        dd($migrationFiles);

        foreach ($migrationFiles as &$value) {
            $value = $value['migration_file'];
        }

        dd($migrationFiles);
    }
}
