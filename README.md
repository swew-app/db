# SWEW/db

Simplest Migration and SqlModel tool


# Usage


# Migration

```php
<?php

// Migration file
use Swew/Db/{Migrate,Migrator};

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

```php
<?php

use Swew/Db/Model;

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

    // By this key use counts [optional] [default: 'id']
    protected function id(): string {
        return 'id';
    }

    // Update updated_at, and add created_at date fields [optional] [default: true]
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
UserModel::query(UserModel::FIND_BY_NAME, 'Jack')->get(); // array
UserModel::query(UserModel::FIND_BY_NAME, 'Jack')->getFirst(); // item
UserModel::query(UserModel::MOST_POPULAR_USER)
    ->offset(2)
    ->limit(1)
    ->getFirst();
    
UserModel::query(UserModel::FIND_BY_NAME, 'Jack')->getFirstItem(); // UserModel
UserModel::query(UserModel::FIND_BY_NAME, 'Jack')->getItems(); // UserModel[]

UserModel::query(UserModel::MOST_POPULAR_USER)->count();
```

## INSERT
```php
$user = UserModel::createItem();

$user->login = 'Mr 007';
$user->name = 'James';

$lastId = UserModel::queryItem(UserModel::INSERT_LOGIN_NAME)->exec()->id();

UserModel::queryItem(UserModel::INSERT_LOGIN_NAME)->exec();

UserModel::query(UserModel::INSERT_LOGIN_NAME, ['login' => 'MyLogin', 'name' => 'My Name'])->exec();
```

## UPDATE
```php
UserModel::query(UserModel::UPDATE_NAME, 'Garry')->where('id', 1)->exec();

UserModel::query(UserModel::UPDATE_NAME)
    ->update('Garry')
    ->where('id', 1)->exec();

UserModel::query(UserModel::UPDATE_NAME)
    ->updateMany(['Garry'])
    ->where('id', 1)->exec();
```

## JOIN
```php
UserModel::query(UserModel::JOIN_COMMENT)->get();

// Paginate
UserModel::query(UserModel::JOIN_COMMENT)->getPages($pageNumber = 1, $perPage = 10);
UserModel::query(UserModel::JOIN_COMMENT)->getPagesWithCount();
```

```php
// Result
[
    'data' => $items, // array
    'page' => 1,
    'next' => 2,
    'prev' => 0,
    // 'count' => 10, // if use ->getPageWithCount()
];

UserModel::query(UserModel::JOIN_COMMENT)->getCursorPages($id = 11, $pageNumber = 2, $perPage = 10);
```
```php
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
$isOk = Model::transaction(function () {
    UserModel::query(UserModel::UPDATE_NAME, 'Leo')->where('id', 1)->exec();
    UserModel::query(UserModel::UPDATE_NAME, 'Don')->where('id', 2)->exec();
    UserModel::query(UserModel::UPDATE_NAME, 'Mike')->where('id', 3)->exec(); 
});
```