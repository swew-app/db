<?php

declare(strict_types=1);

use Swew\Db\Migrate;
use Swew\Db\Migrator;

Migrate::up(function (Migrator $table) {
    $table->tableCreate('jobs');
    $table->id();
    $table->string('name');
    $table->timestamps();
});

Migrate::up(function (Migrator $table) {
    $table->tableCreate('messages');
    $table->id();
    $table->bigInteger('user_id');
    $table->text('message');
    $table->dateTime('time');
    $table->timestamps();
});

Migrate::down(function (Migrator $table) {
    $table->tableDrop('jobs');
});

Migrate::down(function (Migrator $table) {
    $table->tableDrop('messages');
});
