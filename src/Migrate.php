<?php

declare(strict_types=1);

namespace Swew\Db;

final class Migrate
{
    private static array $upList = [];

    private static array $downList = [];

    private function __construct()
    {
    }

    public static function up(callable $callback): void
    {
        $migrator = new Migrator();
        $callback($migrator);
    }
}
