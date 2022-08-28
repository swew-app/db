<?php

declare(strict_types=1);

namespace Swew\Db;

use LogicException;
use Swew\Db\Lib\MigrationColumn;

final class Migrator
{
    private string $table = '';

    private string $tableForDrop = '';

    private array $sqlLines = [];

    private string $queryTablePrefix = '';

    public function __construct(readonly string $type = 'mysql')
    {
    }

    private function addLine(string|MigrationColumn $column, string $prefix = '  '): void
    {
        if (! empty($this->tableForDrop)) {
            throw new LogicException('You cannot delete and create tables at the same time');
        }

        if (is_string($column)) {
            $this->sqlLines[] = $prefix.$column;
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
                $lines[] = '  '.$line->toString();
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
        if (! empty($this->table)) {
            throw new LogicException("The table '$tableName' is already set");
        }

        $this->queryTablePrefix = "CREATE TABLE IF NOT EXISTS `$tableName` (";

        return $this;
    }

    public function tableDrop(string $tableName): self
    {
        if (! empty($this->tableName)) {
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
            'sqlite' => "`$name` INTEGER PRIMARY KEY",
        };

        $this->addLine($idSql);
    }

    public function int(string $name): MigrationColumn
    {
        $column = new MigrationColumn($name);

        $column->setType('INT');

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
}
