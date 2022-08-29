<?php

declare(strict_types=1);

namespace Swew\Db\Parts;

use Swew\Db\Model;

final class MigrationModel extends Model
{
    public function table(): string
    {
        return 'migrations';
    }
}
