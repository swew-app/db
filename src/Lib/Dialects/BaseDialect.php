<?php

declare(strict_types=1);

namespace Swew\Db\Lib\Dialects;

use LogicException;
use Swew\Db\Lib\ColumnSize;

class BaseDialect
{
    public function getNumberType(ColumnSize $size, int $precision, int $scale): string
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

    public function getDateType(ColumnSize $size): string
    {
        return match ($size) {
            ColumnSize::DATE => 'DATE',
            ColumnSize::TIME => 'TIME',
            ColumnSize::DATETIME => 'DATETIME',
            ColumnSize::TIMESTAMP => 'TIMESTAMP',
            ColumnSize::YEAR => 'YEAR',
            default => throw new LogicException('[BaseDialect] Wrong type is passed'),
        };
    }

    public function getStringType(ColumnSize $size): string
    {
        return match ($size) {
            ColumnSize::DATE => 'DATE',
            ColumnSize::TIME => 'TIME',
            ColumnSize::DATETIME => 'DATETIME',
            ColumnSize::TIMESTAMP => 'TIMESTAMP',
            ColumnSize::YEAR => 'YEAR',
            default => throw new LogicException('[BaseDialect] Wrong type is passed'),
        };
    }
}
