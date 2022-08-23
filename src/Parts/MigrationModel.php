<?php

declare(strict_types=1);

namespace Swew\Db\Parts;

use Swew\Db\Model;

class UserModel extends Model {
    public function table(): string
    {
        return 'migrations';
    }
}
