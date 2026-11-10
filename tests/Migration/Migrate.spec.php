<?php

declare(strict_types=1);

use Swew\Db\Migrate;
use Swew\Db\ModelConfig;
use Swew\Db\Parts\MigrationModel;

beforeAll(function () {
    $pdo = getPDO();
    ModelConfig::removePDO();
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
    expect(Migrate::getMigratedFiles())->toBe(['2022_10_19_000003.php', '2022_10_19_000002.php']);
    $batch = MigrationModel::vm()->max('batch')->getValue('batch');
    expect($batch)->toBe(1);

    // All
    Migrate::run(__DIR__.'/stub/**.php', false, 0);
    $batch = MigrationModel::vm()->max('batch')->getValue('batch');
    expect($batch)->toBe(null);
});

it('Migrate [partial rollback preserves unrelated history]', function () {
    Migrate::run(__DIR__.'/stub/migrations/**.php', true);
    Migrate::run(__DIR__.'/stub/migrations/2022_09_28_000001.php', false);
    expect(MigrationModel::vm()->select('migration_file')->getValue())->toBe('2022_08_23_000001.php');
    Migrate::run(__DIR__.'/stub/migrations/**.php', false);
});

it('Migrate [failed history write rolls back schema]', function () {
    $pdo = ModelConfig::getPDO();
    $pdo->exec("CREATE TRIGGER reject_migration BEFORE INSERT ON migrations BEGIN SELECT RAISE(ABORT, 'history failed'); END");
    try {
        expect(fn () => Migrate::run(__DIR__.'/stub/migrations/**.php', true))->toThrow(PDOException::class);
        expect($pdo->inTransaction())->toBe(false);
        expect($pdo->query("SELECT count(*) FROM sqlite_master WHERE name IN ('jobs', 'messages')")->fetchColumn())->toBe(0);
        expect(MigrationModel::vm()->count()->getValue())->toBe(0);
    } finally {
        $pdo->exec('DROP TRIGGER reject_migration');
    }
});
