<?php

declare(strict_types=1);

namespace Swew\Db\Lib\Dialects;

use LogicException;
use Swew\Db\Lib\ColumnSize;
use Swew\Db\Lib\ColumnType;

class BaseDialect
{
    public function getType(ColumnType $type, int|ColumnSize $size, ?int $decimal = null): string
    {
        if ($type === ColumnType::NUMBER) {
            return $this->getNumberType($size, $decimal);
        }

        throw new LogicException('[BaseDialect] Wrong type is passed');
    }

    private function getNumberType(int|ColumnSize $size, ?int $decimal = null): string
    {
        if (!is_null($decimal)) {
            return "FLOAT($size, $decimal)";
        }

        if (is_int($size)) {
            return match (true) {
                $size <= 255 => 'TINYINT',
                $size <= 65535 => 'SMALLINT',
                $size <= 16777215 => 'MEDIUMINT',
                $size <= 4294967295 => 'INT',
                true => 'BIGINT',
            };
        }

        return match ($size) {
            ColumnSize::TINYINT => 'TINYINT',
            ColumnSize::SMALLINT => 'SMALLINT',
            ColumnSize::MEDIUMINT => 'MEDIUMINT',
            ColumnSize::INT => 'INT',
            ColumnSize::BIGINT => 'BIGINT',
            default => throw new LogicException('[BaseDialect] Wrong type is passed'),
        };
    }
}
