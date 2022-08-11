<?php

declare(strict_types=1);

use Swew\Db\Migrator;

it('Migrator [user table]', function () {
    $table = new Migrator();

    $table->tableCreate('users');
    $table->id();
    $table->string('name')->unique();
    $table->string('login', 64)->unique()->index();
    $table->string('password', 64)->default('p@$$');

    $expected = <<<'PHP_TEXT'
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `name` VARCHAR(255) UNIQUE NOT NULL,
  `login` VARCHAR(64) UNIQUE NOT NULL,
  `password` VARCHAR(64) DEFAULT('p@$$') NOT NULL
)
PHP_TEXT;

    expect($table->getSql())->toBe($expected);
});

it('Migrator [drop table]', function () {
    $table = new Migrator();

    $table->tableDrop('users');

    $expected = 'DROP TABLE `users`';

    expect($table->getSql())->toBe($expected);
});
