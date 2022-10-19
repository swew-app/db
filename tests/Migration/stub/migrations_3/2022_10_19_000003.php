<?php

declare(strict_types=1);

use Swew\Db\Migrate;
use Swew\Db\Migrator;

Migrate::up(function (Migrator $table) {
    $table->tableCreate('jobs3');
    $table->id();
    $table->string('name');
    $table->timestamps();
});

Migrate::down(function (Migrator $table) {
    $table->tableDrop('jobs3');
});
