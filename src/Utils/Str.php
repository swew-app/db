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

    public static function camelCase(string $str): string
    {
        $wordWithSpace = ucwords(
            preg_replace(
                '/[-_\/]+/',
                ' ',
                strtolower($str)
            )
        );

        return lcfirst(
            preg_replace(
                '/\s+/',
                '',
                $wordWithSpace
            )
        );
    }
}
