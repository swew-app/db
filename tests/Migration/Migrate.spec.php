<?php

declare(strict_types=1);

use Swew\Db\Migrate;
use Swew\Db\ModelConfig;
use Swew\Db\Parts\MigrationModel;

beforeAll(function () {
    $pdo = getPDO();
    ModelConfig::setPDO($pdo);
});

it('Migrate [up]', function () {
    Migrate::run(__DIR__.'/stub/migrations/**.php', true);

    $countOfMigrationFiles = MigrationModel::vm()->count()->getValue();
    expect($countOfMigrationFiles)->toBe(2);
});

it('Migrate [down]', function () {
    // Adding a second migration step
    Migrate::run(__DIR__.'/stub/migrations_2/**.php', true);
    // Adding a third migration step
    Migrate::run(__DIR__.'/stub/migrations_3/**.php', true);

    // down two last steps
    Migrate::run(__DIR__.'/stub/**.php', false, 2);
    $batch = MigrationModel::vm()->max('batch')->getValue('batch');
    expect($batch)->toBe(1);

    // All
    Migrate::run(__DIR__.'/stub/**.php', false, 0);
    $batch = MigrationModel::vm()->max('batch')->getValue('batch');
    expect($batch)->toBe(null);
});
