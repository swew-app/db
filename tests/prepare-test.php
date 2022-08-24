<?php

declare(strict_types=1);

function getPDO(bool $isCreateUserTable = false): PDO
{
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_STRINGIFY_FETCHES => false,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];
    $pdo = new PDO('sqlite::memory:', 'root', 'password', $options);

    if ($isCreateUserTable) {
        $pdo->exec('CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(64) NOT NULL,
            email VARCHAR(255) NOT NULL,
            password VARCHAR(30)
        )');
    }

    return $pdo;
}

function getFakeUsers(int $count = 1): array {
    $users = [];
    $i = 0;

    while ($count > 0) {
        --$count;
        ++$i;

        $users[] = [
            'name' => "Jon {$i}",
            'email' => "t{$i}@test.xx",
            'password' => 'secret',
        ];
    }

    return $users;
}
