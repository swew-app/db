<?php

declare(strict_types=1);

use Swew\Db\Model;
use Swew\Testing\Model\Stub\UserModel;

beforeAll(function () {
    $pdo = getPDO(true);
    Model::setPDO($pdo);

    $data = [
        [
            'name' => 'Jon',
            'email' => 't1@text.xx',
            'password' => 'secret',
        ],
        [
            'name' => 'Dow',
            'email' => 't2@text.xx',
            'password' => 'secret',
        ],
    ];

    UserModel::vm()->query(UserModel::ADD_USER)->execMany($data);
});

it('Model [get]', function () {
    $res = UserModel::vm()->query(UserModel::ALL_USERS)->get();

    expect($res)->toBe(
        [
            [
                'id' => 1,
                'name' => 'Jon',
                'email' => 't1@text.xx',
                'password' => 'secret',
            ],
            [
                'id' => 2,
                'name' => 'Dow',
                'email' => 't2@text.xx',
                'password' => 'secret',
            ],
        ]
    );
});

it('Model [getFirst]', function () {
    $res = UserModel::vm()->query(UserModel::ALL_USERS)->getFirst();

    expect($res)->toBe([
        'id' => 1,
        'name' => 'Jon',
        'email' => 't1@text.xx',
        'password' => 'secret',
    ]);
});

it('Model [getFirstItem]', function () {
    $res = UserModel::vm()->query(UserModel::ALL_USERS)->getFirstItem();

    $user = new UserModel();
    $user->name = 'Jon';
    $user->email = 't1@text.xx';
    $user->password = '#SALT_secret';

    expect($res)->toEqual($user);
});

it('Model [getItems]', function () {
    $res = UserModel::vm()->query(UserModel::ALL_USERS)->getItems();

    $user1 = new UserModel();
    $user1->name = 'Jon';
    $user1->email = 't1@text.xx';
    $user1->password = '#SALT_secret';

    $user2 = new UserModel();
    $user2->name = 'Dow';
    $user2->email = 't2@text.xx';
    $user2->password = '#SALT_secret';

    expect($res[0])->toEqual($user1);
    expect($res[1])->toEqual($user2);
});

it('Model [update WHERE]', function () {
    UserModel::vm()->query(UserModel::UPDATE_NAME)
        ->where('id', 2)
        ->exec(['Leo']);

    $res = UserModel::vm()->query(UserModel::ALL_USERS)
        ->where('id', 2)
        ->getFirst();

    expect($res)->toBe([
        'id' => 2,
        'name' => 'Leo',
        'email' => 't2@text.xx',
        'password' => 'secret',
    ]);
});

it('Model [WHERE OR WHERE]', function () {
    $res = UserModel::vm()->query(UserModel::GET_USER, ['Mike'])
        ->where('id', 2)
        ->where('id', 3)
        ->orWhere('id', 4)
        ->orWhere('id', 5)
        ->toSql();

    $sql = 'SELECT * FROM users WHERE `id` = ? AND WHERE `id` = ? OR WHERE `id` = ? OR WHERE `id` = ?';

    expect($res['sql'])->toBe($sql);
    expect($res['data'])->toBe(['Mike', 2, 3, 4, 5]);
});

it('Model [LIMIT OFFSET]', function () {
    $res = UserModel::vm()->query(UserModel::GET_USER)
        ->offset(10)
        ->limit(1)
        ->toSql();

    $sql = 'SELECT * FROM users LIMIT 10, 1';

    expect($res['sql'])->toBe($sql);

    $res = UserModel::vm()->query(UserModel::GET_USER)
        ->limit(1, 10)
        ->toSql();

    expect($res['sql'])->toBe($sql);

    $data = UserModel::vm()->query(UserModel::GET_USER)
        ->offset(1)
        ->limit(1)
        ->get();

    expect($data[0]['id'])->toBe(2);
});


it('Model [LastId]', function () {
    $id = UserModel::vm()->query(UserModel::ADD_USER)->exec([
        'name' => 'Alex',
        'email' => 'a13@mail.xx',
        'password' => 'secret',
    ])->id();

    expect($id)->toBe('3');
});
