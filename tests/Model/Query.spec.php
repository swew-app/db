<?php

declare(strict_types=1);

use Swew\Db\Lib\Model\ExecuteQuery;
use Swew\Testing\Model\Stub\MemoryCache;
use Swew\Testing\Model\Stub\UserModel;

it('Query [condition groups preserve SQL and binding order]', function () {
    $model = UserModel::vm();
    $model->setCurrentQueryPDO(getPDO(true));
    $query = $model->query('SELECT ? FROM [TABLE]', [7 => 'seed'])
        ->whereNotIn('id', [9])
        ->orWhere('name', 'Jon')
        ->whereIn('id', [1, 2])
        ->where('id', '>', 0)
        ->groupBy('id')
        ->orderBy('id', 'DESC')
        ->limit(3, 1);

    expect($query->toSql())->toBe([
        'sql' => 'SELECT ? FROM users WHERE (`id` > ? AND (`name` = ?) AND `id` IN (?, ?) AND `id` NOT IN (?)) GROUP BY `id` ORDER BY `id` DESC LIMIT 1, 3',
        'data' => [7 => 'seed', 8 => 0, 9 => 'Jon', 10 => 1, 11 => 2, 12 => 9],
    ]);
    expect($query->toSql()['data'])->toBe([7 => 'seed', 8 => 0, 9 => 'Jon', 10 => 1, 11 => 2, 12 => 9]);
});

it('Query [clones have independent conditions and options]', function () {
    $model = UserModel::vm();
    $model->setCurrentQueryPDO(getPDO(true));
    $original = $model->select()->where('id', '>', 0)->limit(2);
    $copy = clone $original;
    $copy->where('name', 'Jon')->orderBy('id', 'DESC')->limit(1, 2);

    expect($original->toSql())->toBe([
        'sql' => 'SELECT * FROM users WHERE (`id` > ?) LIMIT 2',
        'data' => [0],
    ]);
    expect($copy->toSql())->toBe([
        'sql' => 'SELECT * FROM users WHERE (`id` > ? AND `name` = ?) ORDER BY `id` DESC LIMIT 2, 1',
        'data' => [0, 'Jon'],
    ]);
});

it('Query [cache hits keep first-row and all-rows results separate]', function () {
    $pdo = getPDO(true);
    $pdo->exec("INSERT INTO users (name, email) VALUES ('Before', 'cache@test.xx')");
    $cache = new MemoryCache();
    $query = new ExecuteQuery($pdo, 'SELECT id, name FROM users', UserModel::vm(), $cache);
    $query->cache();

    expect($query->getFirst()['name'])->toBe('Before');
    $pdo->exec("UPDATE users SET name = 'After'");
    expect($query->getFirst()['name'])->toBe('Before');
    expect($query->get()[0]['name'])->toBe('After');
    $pdo->exec("UPDATE users SET name = 'Changed again'");
    expect($query->get()[0]['name'])->toBe('After');
    expect($cache->cache)->toHaveCount(2);
});
