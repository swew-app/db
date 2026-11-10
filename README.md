# SWEW/db

Simple SQL model and query tool

The **swew/db** library is a lightweight and fast PHP library that provides an easy-to-use interface for interacting with databases through SQL models and queries. It's designed to protect against SQL injection attacks by using parameter binding and other security measures.

PHP - should be light and fast!!!

---

# Packages developed by SWEW

> -   [swew/cli](https://packagist.org/packages/swew/cli) - A command-line interface program with formatting and text entry functions.
> -   [swew/test](https://packagist.org/packages/swew/test) - A test framework that is designed to fix the fatal flaw of other test frameworks.
> -   [swew/db](https://packagist.org/packages/swew/db) - A lightweight, fast, and secure PHP library for SQL models and database queries.
> -   [swew/dd](https://packagist.org/packages/swew/dd) - The simplest way to debug variables. As in Laravel.

---

# Install

```sh
composer require swew/db
```

```php
<?php

use Swew\Db\ModelConfig;

// PDO connection init
ModelConfig::init($dsn, $username, $password);
```

## Development

```sh
composer install
composer test        # Run tests
composer check       # Mago analysis, linting, and formatting check (src/)
composer format      # Apply Mago formatting
```

Mago targets PHP 8.3; configuration is in `mago.toml`.
Composer checks show errors only; advisory diagnostics remain available with:

```sh
vendor/bin/mago analyze
vendor/bin/mago lint
```

# SQL migrations

SQL migrations for SQLite, PostgreSQL, and MySQL; no extra dependencies.

```sh
# Installed package: vendor/bin/swew-migrate
# From this repository: php bin/swew-migrate
vendor/bin/swew-migrate create migrations create_users
```

Edit the generated `migrations/<timestamp>_create_users.sql`:

```sql
-- +swew Up
CREATE TABLE users (
    id INTEGER PRIMARY KEY,
    name VARCHAR(255) NOT NULL
);

-- +swew Down
DROP TABLE users;
```

```sh
export SWEW_DB_DSN='sqlite:app.sqlite'
# For PostgreSQL/MySQL, optionally set SWEW_DB_USER and SWEW_DB_PASSWORD.
vendor/bin/swew-migrate status migrations
vendor/bin/swew-migrate up migrations     # Apply all pending migrations
vendor/bin/swew-migrate down migrations   # Roll back only the latest migration
```

The directory defaults to `migrations`. Files use `<positive integer>_<name>.sql`;
versions must be unique and run in numeric order. Creation uses timestamps (one
migration per second). Applied versions are stored in `swew_migrations`, independently
of model table prefixes. Missing/renamed applied files and out-of-order additions
are rejected. Do not edit an applied migration; add a new one instead.

Use directly with the configured model connection or an explicit PDO:

```php
use Swew\Db\Migrator;

$migrator = new Migrator(__DIR__ . '/migrations'); // Uses ModelConfig::getPDO()
// $migrator = new Migrator(__DIR__ . '/migrations', $pdo);
$migrator->up();      // Applied version numbers
$migrator->status();  // Rows: version, name, applied
$migrator->down();    // Rolled-back version, or null
```

Each SQLite/PostgreSQL migration and its version update run in one transaction.
Put `-- +swew NO TRANSACTION` before `Up` for SQL that cannot run transactionally.
MySQL runs without transactions because DDL implicitly commits. Failed
nontransactional migrations may leave partial changes; inspect and repair before
retrying. Migration SQL must not manage its own transactions.

`-- +swew StatementBegin` / `-- +swew StatementEnd` blocks are accepted for
functions and triggers. Each direction is sent to PDO as a complete SQL batch,
so semicolons inside strings or procedure bodies are preserved. Annotations must
be standalone lines, not inside SQL strings. Other directives and Go
migrations are not supported.
Run only one migration process at a time (no cross-process locking).

# DataBase queries

# Model

```php
<?php

use Swew\Db\Model;

class UserModel extends Model {
    // acceptable fields, should be used with default values, so there are no errors in php 8.2
    public ?int   $id = null;
    public string $login = '';
    public string $name = '';
    public string $password = '';
    public int    $rating = 0;

    // Table name [required]
    protected function table(): string {
        return 'users';
    }

    protected function getCache(): bool {
        return true;
    }


    // By this key use counts [optional] [default: 'id']
    protected function id(): string {
        return 'id';
    }

    // Update updated_at, and add created_at date fields [optional] [default: false]
    protected function hasTimestamp(): bool {
        return true;
    }


    protected function cast(): array {
        return [
            'to' => [
                'password' => fn ($str) => password_hash($str, PASSWORD_BCRYPT),
            ],
            'from' => [
                'created_at' => fn(int|string|null $timeStamp) => $timeStamp ? date('Y.m.d - H:i:s', (int)$timeStamp) : '',
                'updated_at' => fn(int|string|null $timeStamp) => $timeStamp ? date('Y.m.d - H:i:s', (int)$timeStamp) : '',
            ],
        ];
    }

    protected function mapTable(): array
    {
        return [
            // 'TABLE' => $this, // default value, fixed
            'T1' => $this,
            'T2' => CommentModel::class,
            'T3' => 'table_name',
        ];
    }

    // SQL Query
    const MOST_POPULAR_USER = 'SELECT id, login, name FROM [TABLE] WHERE rating >= 9';
    const FIND_BY_NAME = 'SELECT id, login, name FROM [TABLE] WHERE name = ?';
    const UPDATE_NAME_BY_ID = 'UPDATE [TABLE] SET name = ? WHERE id = ?';
    const UPDATE_NAME = 'UPDATE [TABLE] SET name = ?';
    const INSERT_LOGIN_NAME = 'INSERT INTO [TABLE] (login, name) VALUES (:login, :name)';
    const JOIN_COMMENT = 'SELECT [T1].name, [T2].comment FROM [T1] JOIN [T2] ON [T1].id=[T2].user_id';
}
```

---

## GET

```php
// const MOST_POPULAR_USER = 'SELECT id, login, name FROM [TABLE] WHERE rating >= 9';
// const FIND_BY_NAME = 'SELECT id, login, name FROM [TABLE] WHERE name = ?';
UserModel::vm()->query(UserModel::FIND_BY_NAME, 'Jack')->get(); // array list
UserModel::vm()->query(UserModel::FIND_BY_NAME, 'Jack')->getFirst(); // array data with first item with limit
UserModel::vm()->query(UserModel::MOST_POPULAR_USER)->getFirst();

UserModel::vm()->query(UserModel::FIND_BY_NAME, 'Jack')->getFirstItem(); // UserModel
UserModel::vm()->query(UserModel::FIND_BY_NAME, 'Jack')->getItems(); // UserModel[]

UserModel::vm()->query(UserModel::MOST_POPULAR_USER)->getValue(); // First value from first item

// Mapped values
UserModel::vm()->query(UserModel::MOST_POPULAR_USER)->getMap(
    fn ($v) => $v['login']
);
```

## insert AND insertMany

> alias for [save](#save)

```php
// const INSERT_LOGIN_NAME = 'INSERT INTO [TABLE] (login, name) VALUES (:login, :name)';
$user = new UserModel();

$user->login = 'Mr 007';
$user->name = 'James';

$lastId = UserModel::vm()->query(UserModel::INSERT_LOGIN_NAME)
    ->setData($user)
    ->exec()
    ->id();

UserModel::vm()
    ->query(UserModel::INSERT_LOGIN_NAME, ['login' => 'MyLogin', 'name' => 'My Name'])
    ->exec();

UserModel::vm()
    ->insert(['login' => 'MyLogin', 'name' => 'My Name']);

UserModel::vm()
    ->insertMany([
      ['login' => 'MyLogin_1', 'name' => 'My Name 1'],
      ['login' => 'MyLogin_2', 'name' => 'My Name 2'],
    ]);
```

## UPDATE

```php
// const UPDATE_NAME = 'UPDATE [TABLE] SET name = ?';
UserModel::vm()
    ->query(UserModel::UPDATE_NAME, 'Garry')
    ->where('id', 1)
    ->exec();

UserModel::vm()
    ->query(UserModel::UPDATE_NAME)
    ->where('id', 1)
    ->exec('Garry');

UserModel::vm()
    ->query(UserModel::UPDATE_NAME)
    ->where('id', 1)
    ->execMany(['Garry']);
```

## COUNT

```php
$count = UserModel::vm()
    ->count()
    ->where('id', '>', 2)
    ->getValue();
```

## JOIN

```php
// const JOIN_COMMENT = 'SELECT [T1].name, [T2].comment FROM [T1] JOIN [T2] ON [T1].id=[T2].user_id';
UserModel::vm()->query(UserModel::JOIN_COMMENT)->get();
```

## PAGINATE

```php
// Paginate

// const JOIN_COMMENT = 'SELECT [T1].name, [T2].comment FROM [T1] JOIN [T2] ON [T1].id=[T2].user_id';
UserModel::vm()->query(UserModel::JOIN_COMMENT)->getPages($pageNumber = 1, $perPage = 10);
UserModel::vm()->query(UserModel::JOIN_COMMENT)->getPagesWithCount();

// Result
[
    'data' => $items, // array
    'page' => 1,
    'next' => 2,
    'prev' => 0,
    // 'count' => 10, // if use ->getPageWithCount()
];
```

```php
// cursor pagination

// const JOIN_COMMENT = 'SELECT [T1].name, [T2].comment FROM [T1] JOIN [T2] ON [T1].id=[T2].user_id';
UserModel::vm()->query(UserModel::JOIN_COMMENT)->getCursorPages($id = 11, $pageNumber = 2, $perPage = 10);

// Result
[
    'data' => $items, // array
    'next_id' => 21,
    'prev_id' => 1,
    'page' => 1,
    'next' => 2,
    'prev' => 0,
];
```

## Transaction

```php
// const UPDATE_NAME = 'UPDATE [TABLE] SET name = ?';
$isOk = UserModel::transaction(function () {
    UserModel::vm()->query(UserModel::UPDATE_NAME, 'Leo')->where('id', 1)->exec();
    UserModel::vm()->query(UserModel::UPDATE_NAME, 'Don')->where('id', 2)->exec();
    UserModel::vm()->query(UserModel::UPDATE_NAME, 'Mike')->where('id', 3)->exec();
});
```

# Query without sql

### select

```php
UserModel::vm()
    ->select('name', 'rating')
    ->where('rating', '>', 4)
    ->getFirst();
    // [
    //     'name' => 'Leo',
    //     'rating' => 5,
    // ],
```

### max

```php
UserModel::vm()
    ->max('rating')
    ->getValue('rating'); // 5
```

### min

```php
UserModel::vm()
    ->min('rating')
    ->getValue(); // 1
```

### save

```php
$user = new UserModel();
$user->name = 'Leo';
$user->login = 'Ninja';
$user->password = 'secret';

$user->save();
```

```php
UserModel::vm()->save([
    'name' => 'Don',
    'login' => 'Ninja',
    'password' => 'secret',
]);
```

### update

`update()` and `delete()` build queries. Add filters, then call `exec()` to execute them.
Without filters, they affect every row.

```php
$user = new UserModel();
$user->name = 'Master Splinter';
$user->email = 's2@mail.xx';
// OR
// $user = [
//     'name' => 'Master Splinter',
//     'email' => 's2@mail.xx',
// ];
UserModel::vm()->update($user)->where('id', 1)->exec();
```

### delete

```php
UserModel::vm()->delete()->where('id', 1)->exec();
```

### soft delete

For soft delete to work, your table must have a nullable integer `deleted_at` field (Unix timestamp).
In your model, there must be a softDelete() method that returns true.

```php
UserModel::vm()->delete()->where('id', 1)->exec();
```

### where

```php
UserModel::vm()->select()->where('id', 1)->exec();
UserModel::vm()->select()->where('id', '=', 1)->exec();
UserModel::vm()->select()->where('id', '!=', 1)->exec();
UserModel::vm()->select()->where('id', '>', 1)->exec();
UserModel::vm()->select()->where('id', '<', 1)->exec();
```

### or where

```php
UserModel::vm()->select()->orWhere('id', 1)->exec();
UserModel::vm()->select()->orWhere('id', '=', 1)->exec();
UserModel::vm()->select()->orWhere('id', '!=', 1)->exec();
UserModel::vm()->select()->orWhere('id', '>', 1)->exec();
UserModel::vm()->select()->orWhere('id', '<', 1)->exec();
```

### where in

```php
UserModel::vm()->select()->whereIn('id', [1, 2, 3])->exec();
```

### where not in

```php
UserModel::vm()->select()->whereNotIn('id', [1, 2, 3])->exec();
```

## limit & offset

```php
UserModel::vm()->select()
    ->offset(2)
    ->limit(1)
    ->get();
```

# Cache

```php
UserModel::vm()
    ->select('name')
    ->where('id', 3)
    ->cache(3600) // seconds
    ->getFirst();
```
