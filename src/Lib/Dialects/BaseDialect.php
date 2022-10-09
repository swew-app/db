<?php

declare(strict_types=1);

namespace Swew\Db\Lib\Dialects;

use LogicException;
use Swew\Db\Lib\ColumnSize;
use Swew\Db\Lib\ColumnType;

class BaseDialect
{
    public function getType(ColumnType $type, ColumnSize $size, int $precision = 0, int $scale = 0): string
    {
        if ($type === ColumnType::NUMBER) {
            return $this->getNumberType($size, $precision, $scale);
        }

        throw new LogicException('[BaseDialect] Wrong type is passed');
    }

    private function getNumberType(ColumnSize $size, int $precision, int $scale): string
    {
        return match ($size) {
            ColumnSize::TINYINT => 'TINYINT',
            ColumnSize::SMALLINT => 'SMALLINT',
            ColumnSize::MEDIUMINT => 'MEDIUMINT',
            ColumnSize::INT => 'INT',
            ColumnSize::BIGINT => 'BIGINT',

            ColumnSize::DECIMAL => "DECIMAL($precision, $scale)",
            ColumnSize::DOUBLE => "DOUBLE($precision, $scale)",
            ColumnSize::FLOAT => "FLOAT($precision, $scale)",
            default => throw new LogicException('[BaseDialect] Wrong type is passed'),
        };
    }
}
