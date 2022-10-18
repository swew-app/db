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
            default => throw new LogicException('[BaseDialect "number"] Wrong type is passed'),
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
            default => throw new LogicException('[BaseDialect "date"] Wrong type is passed'),
        };
    }

    public function getStringType(ColumnSize $size, int $len = 0): string
    {
        return match ($size) {
            ColumnSize::CHAR => ($len ? "CHAR($len)" : "CHAR"),
            // length range from 0 to 65,535
            ColumnSize::VARCHAR => ($len ? "VARCHAR($len)" : "VARCHAR"),
            // default 1  characters
            ColumnSize::BINARY => "BINARY",
            ColumnSize::VARBINARY => "VARBINARY",
            ColumnSize::TINYBLOB => "TINYBLOB",
            // max: 2^16−1  characters
            ColumnSize::BLOB => "BLOB",
            ColumnSize::MEDIUMBLOB => "MEDIUMBLOB",
            ColumnSize::LONGBLOB => "LONGBLOB",
            // max: 255  characters
            ColumnSize::TINYTEXT => "TINYTEXT",
            ColumnSize::TEXT => ($len ? "TEXT($len)" : "TEXT"),
            ColumnSize::MEDIUMTEXT => "MEDIUMTEXT",
            ColumnSize::LONGTEXT => "LONGTEXT",
            default => throw new LogicException('[BaseDialect "string"] Wrong type is passed'),
        };
    }
}
