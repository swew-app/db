<?php

declare(strict_types=1);

use Swew\Db\Migrate;
use Swew\Db\Migrator;

Migrate::up(function (Migrator $table) {
    $table->setSql('SELECT 4');
});

Migrate::up(function (Migrator $table) {
    $table->setSql('SELECT 5');
});

Migrate::down(function (Migrator $table) {
    $table->setSql('SELECT 6');
});
