<?php

declare(strict_types=1);

use Swew\Db\Model;
use Swew\Testing\Model\Stub\UserModel;

beforeAll(function () {
    $pdo = getPDO(true);
    Model::setPDO($pdo);

    $data = getFakeUsers(2);

    UserModel::vm()->query(UserModel::ADD_USER)->execMany($data);
});

it('Model [execMany]', function () {
    $email = 't-exec-many@test.xx';

    UserModel::vm()->query(UserModel::ADD_USER)->execMany([
        [
            'name' => 'Jon X',
            'email' => $email,
            'password' => 'secret',
        ],
    ]);

    $item = UserModel::vm()->query(UserModel::ALL_USERS)->where('email', $email)->getFirstItem();

    expect($item->password)->toBe('#SALT_secret');
});

it('Model [get]', function () {
    $res = UserModel::vm()->query(UserModel::ALL_USERS)
        ->limit(2)
        ->get();

    expect($res)->toBe(
        [
            [
                'id' => 1,
                'name' => 'Jon 1',
                'email' => 't1@test.xx',
                'password' => '#SALT_secret',
            ],
            [
                'id' => 2,
                'name' => 'Jon 2',
                'email' => 't2@test.xx',
                'password' => '#SALT_secret',
            ],
        ]
    );
});

it('Model [getFirst]', function () {
    $res = UserModel::vm()->query(UserModel::ALL_USERS)->getFirst();

    expect($res)->toBe([
        'id' => 1,
        'name' => 'Jon 1',
        'email' => 't1@test.xx',
        'password' => '#SALT_secret',
    ]);
});

it('Model [getFirstItem]', function () {
    $res = UserModel::vm()->query(UserModel::ALL_USERS)->getFirstItem();

    $user = new UserModel();
    $user->name = 'Jon 1';
    $user->email = 't1@test.xx';
    $user->password = '#SALT_secret';

    expect($res)->toEqual($user);
});

it('Model [getItems]', function () {
    $res = UserModel::vm()->query(UserModel::ALL_USERS)->getItems();

    $user1 = new UserModel();
    $user1->name = 'Jon 1';
    $user1->email = 't1@test.xx';
    $user1->password = '#SALT_secret';

    $user2 = new UserModel();
    $user2->name = 'Jon 2';
    $user2->email = 't2@test.xx';
    $user2->password = '#SALT_secret';

    expect($res[0]->password)->toBe('#SALT_secret');

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
        'email' => 't2@test.xx',
        'password' => '#SALT_secret',
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

it('Model [count]', function () {
    $count = UserModel::vm()
        ->count()
        ->where('id', '<', 3)
        ->getValue();

    expect($count)->toBe(2);
});

it('Model [LastId]', function () {
    $id = UserModel::vm()->query(UserModel::ADD_USER)->exec([
        'name' => 'Alex',
        'email' => 'a13@mail.xx',
        'password' => 'secret',
    ])->id();

    expect($id)->toBe('4');
});

it('Model [transaction]', function () {
    $isOk = UserModel::transaction(function () {
        UserModel::vm()->query(UserModel::ADD_USER)->exec([
            'name' => 'Test 3',
            'email' => 't3@test.xx',
            'password' => 'secret',
        ]);
        UserModel::vm()->query(UserModel::ADD_USER)->exec([
            'name' => 'Test 4',
            'email' => 't4@test.xx',
            'password' => 'secret',
        ]);
    });

    expect($isOk)->toBe(true);

    $data = UserModel::vm()
        ->query(UserModel::GET_USER)
        ->where('email', 't4@test.xx')
        ->getFirst();

    expect($data['name'])->toBe('Test 4');
});

it('Model [setData]', function () {
    $user = new UserModel();
    $user->name = 'Jack';
    $user->email = 't_34@test.xx';

    UserModel::vm()->query(UserModel::ADD_USER)
        ->setData($user)
        ->exec();

    $data = UserModel::vm()
        ->query(UserModel::GET_USER)
        ->where('email', 't_34@test.xx')
        ->getFirst();

    expect($data['name'])->toBe('Jack');
});

it('Model [setCast, getCast]', function () {
})->todo();

it('Model [select]', function () {
    $user = UserModel::vm()
        ->select()
        ->getFirstItem();

    expect($user->name)->toBe('Jon 1');
});

it('Model [save]', function () {
    $user = new UserModel();
    $user->name = 'Splinter';
    $user->email = 's1@mail.xx';
    $user->password = 'secret';

    $user->save();

    $res = UserModel::vm()->select('id', 'name')->where('email', 's1@mail.xx')->getFirst();

    expect($res['name'])->toBe('Splinter');
});
