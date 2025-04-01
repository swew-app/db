<?php

declare(strict_types=1);
/*
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_STRINGIFY_FETCHES => false,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];
$dsn = 'mysql:host=mysql;dbname=example;charset=utf8';
$dsn = "pgsql:host=pgsql;dbname=example;options='--client_encoding=UTF8'";
$dsn = 'sqlite:../db.sqlite';
$dsn = 'sqlite::memory:';

$pdo = new PDO($dsn, 'root', 'password', $options);

/*
// my // id INT PRIMARY KEY AUTO_INCREMENT
// pg // id serial PRIMARY KEY
// sl // id INTEGER PRIMARY KEY
$pdo->exec('CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email VARCHAR(255) NOT NULL,
    password VARCHAR(30)
)');
// * /

$sth = $pdo->prepare('INSERT INTO users (email, password) VALUES (:email, :password)');
$sth->execute([
    'email' => 'test@test.ru',
    'password' => password_hash('123456', PASSWORD_BCRYPT),
]);
echo "ID: '".$pdo->lastInsertId()."' \n";
// */
