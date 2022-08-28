<?php

declare(strict_types=1);

namespace Swew\Testing\Model\Stub;

use Swew\Db\Model;

class UserModel extends Model
{
    public function table(): string
    {
        return 'users';
    }

    public string $name = '';

    public string $email = '';

    public string $password = 'secret';

    protected function getCast(): array
    {
        return [
            // 'password' => fn(string $s) => "#SALT_$s",
        ];
    }

    protected function setCast(): array
    {
        return [
            'password' => fn (string $s) => "#SALT_$s",
        ];
    }

    // SQL
    const ALL_USERS = 'SELECT id, name, email, password FROM [TABLE]';

    const ADD_USER = 'INSERT INTO [TABLE] (name, email, password) VALUES (:name, :email, :password)';

    const UPDATE_NAME = 'UPDATE [TABLE] SET name = ?';

    const GET_USER = 'SELECT * FROM [TABLE]';
}
