<?php

declare(strict_types=1);

namespace Swew\Db;

use PDO;
use Swew\Db\Utils\Files;

final class Migrate
{
    private static array $filesList = [];

    private static array $callbackList = [];

    private static string $dbName = '';

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
        self::$filesList = Files::getFilesByPattern($filePattern);

        // $migrator = new Migrator();
        // $callback($migrator);
    }

    public static function getMigrationsRecord(PDO $pdo): void
    {
    }
}
