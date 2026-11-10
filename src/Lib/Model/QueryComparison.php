<?php

declare(strict_types=1);

namespace Swew\Db\Lib\Model;

use LogicException;
use Swew\Db\Model;

/** @internal */
final class QueryComparison
{
    /** @return array{0: string, 1?: mixed} */
    public static function build(Model $model, array $args): array
    {
        $key = '';
        $comp = '=';
        $val = '';
        $count = count($args);

        if ($count === 2) {
            $key = $args[0];
            $val = $args[1];
        } elseif ($count === 3) {
            $key = $args[0];
            $comp = $args[1];
            $val = $args[2];
        } else {
            throw new \LogicException('Wrong parameters');
        }

        if (!is_string($key) || !is_string($comp)) {
            throw new LogicException('Column and comparison operator must be strings');
        }

        $val = $model->castSetValue($key, $val);

        $comp = strtoupper(trim($comp));
        if (!in_array($comp, ['=', '!=', '<>', '>', '>=', '<', '<=', 'LIKE', 'NOT LIKE', 'IS', 'IS NOT'], true)) {
            throw new LogicException('Invalid comparison operator');
        }

        if (is_null($val)) {
            $comp = match ($comp) {
                '=', 'IS' => 'IS NULL',
                '!=', '<>', 'IS NOT' => 'IS NOT NULL',
                default => throw new LogicException('Invalid NULL comparison'),
            };

            return ["`$key` $comp"];
        }

        return ["`$key` $comp ?", $val];
    }
}
