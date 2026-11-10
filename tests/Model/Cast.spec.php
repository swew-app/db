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

it('cast overrides and hydration apply once to nullable properties', function () {
    $model = new class extends UserModel {
        public ?int $id = null;

        public ?string $created_at = null;

        protected function cast(): array
        {
            return ['from' => [
                'name' => fn ($value) => $value.'!',
                'created_at' => fn ($value) => 'time:'.$value,
            ]];
        }
    };
    $query = $model->query("SELECT 42 AS id, 'Jack' AS name, 123 AS created_at");
    foreach ([$query->getFirstItem(), $query->getItems()[0]] as $item) {
        expect([$item->id, $item->name, $item->created_at])->toBe([42, 'Jack!', 'time:123']);
    }
});
