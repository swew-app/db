<?php

declare(strict_types=1);

namespace Swew\Db\Utils;

final class Str
{
    private function __construct()
    {
    }

    public static function valueToString(mixed $value): string
    {
        if (is_null($value)) {
            return 'NULL';
        }

        if (true === $value) {
            return 'TRUE';
        }

        if (false === $value) {
            return 'FALSE';
        }

        if (\is_string($value)) {
            return "'$value'";
        }

        return (string) $value;
    }
}
