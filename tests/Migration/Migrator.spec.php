<?php

declare(strict_types=1);

use Swew\Db\Lib\ColumnSize;
use Swew\Db\Migrator;

it('Migrator [user table]', function () {
    $table = new Migrator();

    $table->tableCreate('users');
    $table->id();
    $table->string('name')->unique();
    $table->string('login', 64)->unique()->index();
    $table->string('password', 64)->default('p@$$');
    $table->int('rating')->nullable();

    $expected = <<<'PHP_TEXT'
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `name` VARCHAR(255) UNIQUE NOT NULL,
  `login` VARCHAR(64) UNIQUE NOT NULL,
  `password` VARCHAR(64) DEFAULT('p@$$') NOT NULL,
  `rating` INT
)
PHP_TEXT;

    expect($table->getSql())->toBe($expected);
})->skip();

it('Migrator [drop table]', function () {
    $table = new Migrator();

    $table->tableDrop('users');

    $expected = 'DROP TABLE `users`';

    expect($table->getSql())->toBe($expected);
})->skip();

it('Migrator [mysql]', function () {
    $table = new Migrator('mysql');
    $table->tableCreate('list');

    $table->number('rating_1', ColumnSize::INT);
    $table->number('rating_2', 255);
    $table->number('rating_3', 1024)->nullable();

    $expected = <<<'PHP_TEXT'
CREATE TABLE IF NOT EXISTS `list` (
  `rating_1` INT NOT NULL,
  `rating_2` TINYINT NOT NULL,
  `rating_3` SMALLINT
)
PHP_TEXT;

    expect($table->getSql())->toBe($expected);
});
