<?php

declare(strict_types=1);

namespace Swew\Db;

use LogicException;
use Swew\Db\Lib\ColumnSize;
use Swew\Db\Lib\Dialects\BaseDialect;
use Swew\Db\Lib\Dialects\MysqlDialect;
use Swew\Db\Lib\Dialects\SqlLiteDialect;
use Swew\Db\Lib\MigrationColumn;

final class Migrator
{
    private string $table = '';

    private string $tableForDrop = '';

    private array $sqlLines = [];

    private string $queryTablePrefix = '';

    public function __construct(readonly string $type)
    {
    }

    private function addLine(string|MigrationColumn $column, string $prefix = '  '): void
    {
        if (!empty($this->tableForDrop)) {
            throw new LogicException('You cannot delete and create tables at the same time');
        }

        if (is_string($column)) {
            $this->sqlLines[] = $prefix . $column;
        } else {
            $this->sqlLines[] = $column;
        }
    }

    public function setSql(string $sqlQuery): void
    {
        $this->sqlLines[] = $sqlQuery;
    }

    public function getSql(): string
    {
        $lines = [];

        foreach ($this->sqlLines as $line) {
            if (is_string($line)) {
                $lines[] = $line;
            } elseif ($line instanceof MigrationColumn) {
                $lines[] = '  ' . $line->toString();
            }
        }

        $columns = implode(",\n", $lines);

        $sqlLines = [
            $this->queryTablePrefix,
            $columns,
        ];

        if (str_contains($this->queryTablePrefix, '(')) {
            $sqlLines[] = ')';
        }

        return trim(implode("\n", $sqlLines));
    }

    // table

    public function tableCreate(string $tableName): self
    {
        if (!empty($this->table)) {
            throw new LogicException("The table '$tableName' is already set");
        }

        $this->queryTablePrefix = "CREATE TABLE IF NOT EXISTS `$tableName` (";

        return $this;
    }

    public function tableDrop(string $tableName): self
    {
        if (!empty($this->tableName)) {
            throw new LogicException("The table '$tableName' is already set");
        }

        $this->queryTablePrefix = "DROP TABLE `$tableName`";

        return $this;
    }

    // columns

    public function id(string $name = 'id'): void
    {
        $idSql = match ($this->type) {
            'mysql' => "`$name` INT SERIAL",
            'pgsql' => "`$name` serial PRIMARY KEY",
            'sqlite' => "`$name` INTEGER PRIMARY KEY AUTOINCREMENT",
        };

        $this->addLine($idSql);
    }

    //region [NUMBER]
    private function number(string $name, ColumnSize $size, int $precision = 0, int $scale = 0): MigrationColumn
    {
        $column = new MigrationColumn($name);

        $type = $this->dialect()->getNumberType($size, $precision, $scale);

        $column->setType($type);

        $this->addLine($column);

        return $column;
    }

    public function bigIncrements(string $name): MigrationColumn
    {
        $column = $this->number($name, ColumnSize::BIGINT);
        $column->setSuffix('AUTO_INCREMENT');
        return $column;
    }

    public function bigInteger(string $name): MigrationColumn
    {
        return $this->number($name, ColumnSize::BIGINT);
    }

    public function decimal(string $name, int $precision, int $scale): MigrationColumn
    {
        return $this->number($name, ColumnSize::DECIMAL, $precision, $scale);
    }

    public function double(string $name, int $precision, int $scale): MigrationColumn
    {
        return $this->number($name, ColumnSize::DOUBLE, $precision, $scale);
    }

    public function float(string $name, int $precision, int $scale): MigrationColumn
    {
        return $this->number($name, ColumnSize::FLOAT, $precision, $scale);
    }

    public function integer(string $name): MigrationColumn
    {
        return $this->number($name, ColumnSize::INT);
    }

    public function smallInteger(string $name): MigrationColumn
    {
        return $this->number($name, ColumnSize::SMALLINT);
    }

    public function smallIncrements(string $name): MigrationColumn
    {
        $column = $this->number($name, ColumnSize::SMALLINT);
        $column->setSuffix('AUTO_INCREMENT');
        return $column;
    }

    public function mediumInteger(string $name): MigrationColumn
    {
        return $this->number($name, ColumnSize::MEDIUMINT);
    }

    public function tinyInteger(string $name): MigrationColumn
    {
        return $this->number($name, ColumnSize::TINYINT);
    }

    public function tinyIncrements(string $name): MigrationColumn
    {
        $column = $this->number($name, ColumnSize::TINYINT);
        $column->setSuffix('AUTO_INCREMENT');
        return $column;
    }

    public function unsignedBigInteger(string $name): MigrationColumn
    {
        $column = $this->number($name, ColumnSize::BIGINT);
        $column->setSuffix('UNSIGNED');
        return $column;
    }

    public function unsignedDecimal(string $name, int $scale = 2): MigrationColumn
    {
        $column = $this->number($name, ColumnSize::DECIMAL);
        $column->setSuffix('UNSIGNED');
        return $column;
    }

    public function unsignedInteger(string $name): MigrationColumn
    {
        $column = $this->number($name, ColumnSize::INT);
        $column->setSuffix('UNSIGNED');
        return $column;
    }

    public function unsignedMediumInteger(string $name): MigrationColumn
    {
        $column = $this->number($name, ColumnSize::MEDIUMINT);
        $column->setSuffix('UNSIGNED');
        return $column;
    }

    public function unsignedSmallInteger(string $name): MigrationColumn
    {
        $column = $this->number($name, ColumnSize::SMALLINT);
        $column->setSuffix('UNSIGNED');
        return $column;
    }

    public function unsignedTinyInteger(string $name): MigrationColumn
    {
        $column = $this->number($name, ColumnSize::TINYINT);
        $column->setSuffix('UNSIGNED');
        return $column;
    }
    //endregion

    //region [DATE]
    public function dateTime(string $name): void
    {
        $column = new MigrationColumn($name);

        $type = $this->dialect()->getDateType(ColumnSize::DATETIME);

        $column->setType($type);

        $this->addLine($column);
    }

    public function date(string $name): void
    {
        $column = new MigrationColumn($name);

        $type = $this->dialect()->getDateType(ColumnSize::DATE);

        $column->setType($type);

        $this->addLine($column);
    }

    public function time(string $name): void
    {
        $column = new MigrationColumn($name);

        $type = $this->dialect()->getDateType(ColumnSize::TIME);

        $column->setType($type);

        $this->addLine($column);
    }

    public function timestamp(string $name): void
    {
        $column = new MigrationColumn($name);

        $type = $this->dialect()->getDateType(ColumnSize::TIMESTAMP);

        $column->setType($type);

        $this->addLine($column);
    }

    public function year(string $name): void
    {
        $column = new MigrationColumn($name);

        $type = $this->dialect()->getDateType(ColumnSize::YEAR);

        $column->setType($type);

        $this->addLine($column);
    }
    //endregion

    //region [TEXT]
    public function string(string $name, int $len = 255): MigrationColumn
    {
        $column = new MigrationColumn($name);

        $type = $this->dialect()->getStringType(ColumnSize::VARCHAR, $len);

        $column->setType($type);

        $this->addLine($column);

        return $column;
    }

    public function longText(string $name): MigrationColumn
    {
        $column = new MigrationColumn($name);

        $type = $this->dialect()->getStringType(ColumnSize::LONGTEXT);

        $column->setType($type);

        $this->addLine($column);

        return $column;
    }

    public function mediumText(string $name): MigrationColumn
    {
        $column = new MigrationColumn($name);

        $type = $this->dialect()->getStringType(ColumnSize::MEDIUMTEXT);

        $column->setType($type);

        $this->addLine($column);

        return $column;
    }

    public function text(string $name): MigrationColumn
    {
        $column = new MigrationColumn($name);

        $type = $this->dialect()->getStringType(ColumnSize::TEXT);

        $column->setType($type);

        $this->addLine($column);

        return $column;
    }

    public function tinyText(string $name): MigrationColumn
    {
        $column = new MigrationColumn($name);

        $type = $this->dialect()->getStringType(ColumnSize::TINYTEXT);

        $column->setType($type);

        $this->addLine($column);

        return $column;
    }

    public function char(string $name, int $len = 0): MigrationColumn
    {
        $column = new MigrationColumn($name);

        $type = $this->dialect()->getStringType(ColumnSize::CHAR, $len);

        $column->setType($type);

        $this->addLine($column);

        return $column;
    }
    //endregion

    //region [TIMESTAMP]
    public function timestamps(): void
    {
        $column = new MigrationColumn('created_at');
        $column->setType('datetime');
        $column->default('0000-00-00 00:00:00')->nullable();
        $this->addLine($column);

        $column = new MigrationColumn('updated_at');
        if ($this->type === 'sqlite') {
            $column->setType('TEXT');
        } else {
            $column->setType('datetime');
            $column->default('0000-00-00 00:00:00')->nullable();
        }
        $this->addLine($column);
    }

    //endregion

    private function dialect(): BaseDialect
    {
        if ('sqlite' === $this->type) {
            return new SqlLiteDialect();
        }

        if ('mysql' === $this->type) {
            return new MysqlDialect();
        }

        throw new LogicException('[Migrator] No suitable database driver is specified.');
    }
}
