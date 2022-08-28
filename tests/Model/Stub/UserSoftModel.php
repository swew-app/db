<?php

declare(strict_types=1);

namespace Swew\Testing\Model\Stub;

use Swew\Db\Model;

class UserSoftModel extends Model
{
    public function table(): string
    {
        return 'users';
    }

    protected function softDelete(): bool
    {
        return true;
    }

    // sql
    const ALL_USERS = 'SELECT * FROM [TABLE]';
}
