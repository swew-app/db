# SWEW/db

Simplest Migration and SqlModel tool

A super __lightweight__ tool for creating __migrations__ or interacting with a database in PHP.

PHP - should be light and fast!!!

# Usage

# Migration

```php
<?php

// Migration file
use Swew\Db\{Migrate,Migrator};

Migrate::up(function (Migrator $table) {
    $table->tableCreate('users');
    $table->id();
    $table->string('name')->unique();
    $table->string('login', 64)->unique()->index();
    $table->string('password', 64)->default('123456');
    $table->text('password')->fulltext();
    $table->int('rating')->nullable();
    $table->timestamp();
});

Migrate::down(function (Migrator $table) {
    $table->tableDrop('users');
});
```

## Run migration

```php
<?php

use PDO;
use Swew\Db\{Migrate,ModelConfig};

// PDO connection
$pdo = new PDO('sqlite:' . __DIR__ . '/database.sqlite');
ModelConfig::setPDO($pdo);

// "**" - is alias for sub folders
$filePattern = __DIR__ . '/migrations/**.php';
// Run "UP" migrations
$isUpMigration = true;

Migrate::run($filePattern, $isUpMigration);
```

---

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

    protected function getCast(): array
    {
        return [
            // Default casting
            'created_at' => fn ($timeStamp) => date('Y/m/d - H:i', strtotime($timeStamp)),
            'updated_at' => fn ($timeStamp) => date('Y/m/d - H:i', strtotime($timeStamp)),
        ];
    }

    protected function setCast(): array
    {
        return [
            'password' => fn ($str) => password_hash($str, PASSWORD_BCRYPT),
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

## GET
```php
UserModel::vm()->query(UserModel::FIND_BY_NAME, 'Jack')->get(); // array
UserModel::vm()->query(UserModel::FIND_BY_NAME, 'Jack')->getFirst(); // item
UserModel::vm()->query(UserModel::MOST_POPULAR_USER)
    ->offset(2)
    ->limit(1)
    ->getFirst();

UserModel::vm()->query(UserModel::FIND_BY_NAME, 'Jack')->getFirstItem(); // UserModel
UserModel::vm()->query(UserModel::FIND_BY_NAME, 'Jack')->getItems(); // UserModel[]

UserModel::vm()->query(UserModel::MOST_POPULAR_USER)->getValue(); // First value from first item
```

## INSERT
```php
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
UserModel::vm()->query(UserModel::JOIN_COMMENT)->get();

// Paginate
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
$isOk = UserModel::transaction(function () {
    UserModel::vm()->query(UserModel::UPDATE_NAME, 'Leo')->where('id', 1)->exec();
    UserModel::vm()->query(UserModel::UPDATE_NAME, 'Don')->where('id', 2)->exec();
    UserModel::vm()->query(UserModel::UPDATE_NAME, 'Mike')->where('id', 3)->exec();
});
```

## Query without sql

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

### update

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

For soft delete to work, your table must have a deleted_at field of type DATETIME or (TEXT for SQLite) with a default value of NULL.
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


# Cache

```php
UserModel::vm()
    ->select('name')
    ->where('id', 3)
    ->cache(3600) // seconds
    ->getFirst();
```
