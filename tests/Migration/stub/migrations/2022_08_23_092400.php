<?php

declare(strict_types=1);

use Swew\Db\Migrate;
use Swew\Db\Migrator;

Migrate::up(function (Migrator $table) {
    $table->setSql('SELECT 1');
});

Migrate::up(function (Migrator $table) {
    $table->setSql('SELECT 2');
});

Migrate::down(function (Migrator $table) {
    $table->setSql('SELECT 2');
});
