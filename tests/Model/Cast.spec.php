<?php

declare(strict_types=1);


use Swew\Db\ModelConfig;
use Swew\Testing\Model\Stub\CastModel;
use Swew\Testing\Model\Stub\UserModel;

beforeAll(function () {
    $pdo = getPDO(true);

    ModelConfig::removePDO();
    ModelConfig::setPDO($pdo);
    $data = getFakeUsers(2);

    UserModel::vm()->query(UserModel::ADD_USER)->execMany($data);
});

it('cast get', function () {
    CastModel::vm()->insert([
        'name' => 'Jack From Cast Model Spec',
        'email' => '123@test.xxx',
        'password' => '123456'
    ]);

    $item = CastModel::vm()->select()->where('email', '123@test.xxx')->getFirst();

//    dd($item);

    expect($item['email'])->toBe('123@TEST.COM');
});
