<?php

declare(strict_types=1);

namespace Swew\Db;

use LogicException;
use Swew\Db\Lib\ColumnSize;
use Swew\Db\Lib\ColumnType;
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
            'mysql' => "`$name` INT PRIMARY KEY AUTO_INCREMENT",
            'pgsql' => "`$name` serial PRIMARY KEY",
            'sqlite' => "`$name` INTEGER PRIMARY KEY AUTOINCREMENT",
        };

        $this->addLine($idSql);
    }

    public function number(string $name, int|ColumnSize $size = 4294967295, ?int $decimal = null): MigrationColumn
    {
        $column = new MigrationColumn($name);

        $type = $this->dialect()->getType(ColumnType::NUMBER, $size, $decimal);

        $column->setType($type);

        $this->addLine($column);

        return $column;
    }

    //region [TEXT]
    public function char(string $name, int $length = 1): MigrationColumn
    {
        $column = new MigrationColumn($name);

        $column->setType("CHAR($length)");

        $this->addLine($column);

        return $column;
    }

    public function string(string $name, int $length = 255): MigrationColumn
    {
        $column = new MigrationColumn($name);

        $column->setType("VARCHAR($length)");

        $this->addLine($column);

        return $column;
    }

    public function text(string $name, int $length = 255): MigrationColumn
    {
        $column = new MigrationColumn($name);

        $column->setType("TEXT($length)");

        $this->addLine($column);

        return $column;
    }
    //endregion

    //region [TIMESTAMP]
    public function timestamp(): void
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
