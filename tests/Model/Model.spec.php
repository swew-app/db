<?php

declare(strict_types=1);

use Swew\Db\Model;
use Swew\Testing\Model\Stub\UserModel;
use Swew\Testing\Model\Stub\UserSoftModel;

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

it('Model [WHERE]', function () {
    $res = UserModel::vm()->query(UserModel::GET_USER, ['Mike'])
        ->where('id', 2)
        ->where('id', 3)
        ->toSql();

    $sql = 'SELECT * FROM users WHERE (`id` = ? AND `id` = ?)';

    expect($res['sql'])->toBe($sql);
    expect($res['data'])->toBe(['Mike', 2, 3]);
});

it('Model [WHERE OR WHERE]', function () {
    $res = UserModel::vm()->query(UserModel::GET_USER, ['Mike'])
        ->where('id', 2)
        ->where('id', 3)
        ->orWhere('id', 4)
        ->orWhere('id', 5)
        ->toSql();

    $sql = 'SELECT * FROM users WHERE (`id` = ? AND `id` = ? AND (`id` = ? OR `id` = ?))';

    expect($res['sql'])->toBe($sql);
    expect($res['data'])->toBe(['Mike', 2, 3, 4, 5]);
});

it('Model [OR WHERE]', function () {
    $res = UserModel::vm()->query(UserModel::GET_USER, ['Mike'])
        ->orWhere('id', 4)
        ->orWhere('id', 5)
        ->toSql();

    $sql = 'SELECT * FROM users WHERE ((`id` = ? OR `id` = ?))';

    expect($res['sql'])->toBe($sql);
    expect($res['data'])->toBe(['Mike', 4, 5]);
});

it('Model [WHERE IN]', function () {
    $res = UserModel::vm()->query(UserModel::GET_USER, ['Mike'])
        ->whereIn('id', [4, 3])
        ->whereIn('name', ['Leo', 'Don'])
        ->toSql();

    $sql = 'SELECT * FROM users WHERE (`id` IN (?, ?) AND `name` IN (?, ?))';

    expect($res['sql'])->toBe($sql);
    expect($res['data'])->toBe(['Mike', 4, 3, 'Leo', 'Don']);
});

it('Model [WHERE AND WHERE IN]', function () {
    $res = UserModel::vm()->query(UserModel::GET_USER, ['Mike'])
        ->where('id', 4)
        ->whereIn('name', ['Leo', 'Don'])
        ->toSql();

    $sql = 'SELECT * FROM users WHERE (`id` = ? AND `name` IN (?, ?))';

    expect($res['sql'])->toBe($sql);
    expect($res['data'])->toBe(['Mike', 4, 'Leo', 'Don']);
});

it('Model [WHERE NOT IN]', function () {
    $res = UserModel::vm()->query(UserModel::GET_USER, ['Mike'])
        ->whereIn('id', [4, 3])
        ->whereNotIn('name', ['Leo', 'Don'])
        ->toSql();

    $sql = 'SELECT * FROM users WHERE (`id` IN (?, ?) AND `name` NOT IN (?, ?))';

    expect($res['sql'])->toBe($sql);
    expect($res['data'])->toBe(['Mike', 4, 3, 'Leo', 'Don']);
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

it('Model [setData, getCast]', function () {
    $user = new UserModel();
    $user->name = 'Jack';
    $user->email = 't_34@test.xx';

    UserModel::vm()->query(UserModel::ADD_USER)
        ->setData($user)
        ->exec();

    $data = UserSoftModel::vm() // UserSoftModel - has "getCast" method
        ->query(UserModel::GET_USER)
        ->where('email', 't_34@test.xx')
        ->getFirst();

    expect($data['name'])->toBe('JACK');
});

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

it('Model [update]', function () {
    $user = new UserModel();
    $user->name = 'Master Splinter';
    $user->email = 's2@mail.xx';
    // $user = [
    //     'name' => 'Master Splinter',
    //     'email' => 's2@mail.xx',
    // ];

    UserModel::vm()->update($user)->where('id', 1)->exec();

    $res = UserModel::vm()->select('name', 'password')->where('email', 's2@mail.xx')->getFirst();

    expect($res['name'])->toBe('Master Splinter');
    expect($res['password'])->toBe('#SALT_secret');
});

it('Model [delete]', function () {
    $count = UserModel::vm()->count()->where('id', 1)->getValue();
    expect($count)->toBe(1);

    UserModel::vm()->delete()->where('id', 1)->exec();

    $count = UserModel::vm()->count()->where('id', 1)->getValue();
    expect($count)->toBe(0);
});

it('Model [softDelete]', function () {
    $count = UserSoftModel::vm()->count()->where('id', 2)->getValue();
    expect($count)->toBe(1);

    UserSoftModel::vm()->delete()->where('id', 2)->exec();

    $count = UserSoftModel::vm()->count()->where('id', 2)->getValue();
    expect($count)->toBe(0);

    $arr = UserSoftModel::vm()->select()->where('id', 2)->get();
    expect(count($arr))->toBe(0);

    $res = UserSoftModel::vm()->query(UserSoftModel::ALL_USERS)->where('id', 2)->get();

    expect(count($res))->toBe(1);
});
