<?php

declare(strict_types=1);

use Swew\Db\Migrate;
use Swew\Db\ModelConfig;

beforeAll(function () {
    $pdo = getPDO();
    ModelConfig::setPDO($pdo);
});

it('Migrate [add files]', function () {
    Migrate::searchFiles(__DIR__ . '/stub/**.php');

    // Migrate::run
});
