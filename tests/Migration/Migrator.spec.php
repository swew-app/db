<?php

declare(strict_types=1);

use Swew\Db\Migrator;

it('Migrator [user table mysql]', function () {
    $table = new Migrator('mysql');

    $table->tableCreate('users');
    $table->id();
    $table->string('name')->unique();
    $table->string('login', 64)->unique()->index();
    $table->string('password', 64)->default('p@$$');
    $table->integer('rating')->nullable();

    // `id` INT PRIMARY KEY AUTO_INCREMENT,
    $expected = <<<'PHP_TEXT'
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT SERIAL,
  `name` VARCHAR(255) UNIQUE NOT NULL,
  `login` VARCHAR(64) UNIQUE NOT NULL,
  `password` VARCHAR(64) DEFAULT('p@$$') NOT NULL,
  `rating` INT
)
PHP_TEXT;

    expect($table->getSql())->toBe($expected);
})->only();

it('Migrator [drop table]', function () {
    $table = new Migrator('mysql');

    $table->tableDrop('users');

    $expected = 'DROP TABLE `users`';

    expect($table->getSql())->toBe($expected);
});

it('Migrator [mysql numbers]', function () {
    $table = new Migrator('mysql');
    $table->tableCreate('list');

    $table->bigIncrements('bigIncrements_col');
    $table->bigInteger('bigInteger_col');
    $table->decimal('decimal_col', 5, 2);
    $table->double('double_col', 4, 3);
    $table->float('float_col', 3, 4);
    $table->integer('integer_col');
    $table->mediumInteger('mediumInteger_col');
    $table->tinyInteger('tinyInteger_col');
    $table->unsignedBigInteger('unsignedBigInteger_col');
    $table->unsignedDecimal('unsignedDecimal_col');
    $table->unsignedInteger('unsignedInteger_col');
    $table->unsignedMediumInteger('unsignedMediumInteger_col');
    $table->unsignedSmallInteger('unsignedSmallInteger_col');
    $table->unsignedTinyInteger('unsignedTinyInteger_col');
    $table->smallIncrements('smallIncrements_col');
    $table->smallInteger('smallInteger_col');
    $table->tinyIncrements('tinyIncrements_col');

    $expected = <<<'PHP_TEXT'
CREATE TABLE IF NOT EXISTS `list` (
  `bigIncrements_col` BIGINT AUTO_INCREMENT NOT NULL,
  `bigInteger_col` BIGINT NOT NULL,
  `decimal_col` DECIMAL(5, 2) NOT NULL,
  `double_col` DOUBLE(4, 3) NOT NULL,
  `float_col` FLOAT(3, 4) NOT NULL,
  `integer_col` INT NOT NULL,
  `mediumInteger_col` MEDIUMINT NOT NULL,
  `tinyInteger_col` TINYINT NOT NULL,
  `unsignedBigInteger_col` BIGINT UNSIGNED NOT NULL,
  `unsignedDecimal_col` DECIMAL(0, 0) UNSIGNED NOT NULL,
  `unsignedInteger_col` INT UNSIGNED NOT NULL,
  `unsignedMediumInteger_col` MEDIUMINT UNSIGNED NOT NULL,
  `unsignedSmallInteger_col` SMALLINT UNSIGNED NOT NULL,
  `unsignedTinyInteger_col` TINYINT UNSIGNED NOT NULL,
  `smallIncrements_col` SMALLINT AUTO_INCREMENT NOT NULL,
  `smallInteger_col` SMALLINT NOT NULL,
  `tinyIncrements_col` TINYINT AUTO_INCREMENT NOT NULL
)
PHP_TEXT;

    expect($table->getSql())->toBe($expected);
});

it('Migrator [mysql date]', function () {
    $table = new Migrator('mysql');
    $table->tableCreate('list');

    $table->dateTime('dateTime_col');
    $table->date('date_col');
    $table->time('time_col');
    $table->timestamp('timestamp_col');
    $table->year('year_col');

    $expected = <<<'PHP_TEXT'
CREATE TABLE IF NOT EXISTS `list` (
  `dateTime_col` DATETIME NOT NULL,
  `date_col` DATE NOT NULL,
  `time_col` TIME NOT NULL,
  `timestamp_col` TIMESTAMP NOT NULL,
  `year_col` YEAR NOT NULL
)
PHP_TEXT;

    expect($table->getSql())->toBe($expected);
});

it('Migrator [mysql string]', function () {
    $table = new Migrator('mysql');
    $table->tableCreate('list');

    $table->string('string_col');
    $table->longText('longText_col');
    $table->mediumText('mediumText_col');
    $table->text('text_col');
    $table->tinyText('mediumText_col');
    $table->char('char_col');

    $expected = <<<'PHP_TEXT'
CREATE TABLE IF NOT EXISTS `list` (
  `string_col` TEXT NOT NULL,
  `longText_col` LONGTEXT NOT NULL,
  `mediumText_col` MEDIUMTEXT NOT NULL,
  `text_col` TEXT NOT NULL,
  `mediumText_col` TINYTEXT NOT NULL,
  `char_col` CHAR NOT NULL
)
PHP_TEXT;

    expect($table->getSql())->toBe($expected);
});
