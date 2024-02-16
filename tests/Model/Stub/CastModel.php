<?php

namespace Swew\Testing\Model\Stub;

use Swew\Db\Model;

class CastModel extends Model
{
    protected function table(): string
    {
        return 'users';
    }

    protected function hasTimestamp(): bool {
        return true;
    }

    protected function castSet(): array
    {
        return [
            'email' => function (string $mail): string {
                [$prefix, $addr] = explode('@', $mail);

                if ($addr === 'text.xxx') {
                    return "{$prefix}@TEST.COM";
                }

                return $mail;
            },

            'password' => fn(string $str): string => 'SECRET',
        ];
    }
}
