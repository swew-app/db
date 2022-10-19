<?php

declare(strict_types=1);

use Swew\Db\Migrate;
use Swew\Db\ModelConfig;
use Swew\Db\Parts\MigrationModel;

beforeAll(function () {
    $pdo = getPDO();
    ModelConfig::setPDO($pdo);
});

it('Migrate [add files]', function () {
    Migrate::run(__DIR__.'/stub/**.php', true, ModelConfig::getPDO());

    $countOfMigrationFiles = MigrationModel::vm()->count()->getValue();
    expect($countOfMigrationFiles)->toBe(2);
});
