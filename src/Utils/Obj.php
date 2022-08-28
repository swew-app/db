<?php

declare(strict_types=1);

namespace Swew\Db\Utils;

final class Obj
{
    private function __construct()
    {
    }

    public static function getObjectVars(mixed $object): array
    {
        return get_object_vars($object);
    }
}
