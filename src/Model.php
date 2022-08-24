<?php

declare(strict_types=1);

namespace Swew\Db;

use LogicException;
use PDO;
use Swew\Db\Lib\Model\ExecuteQuery;

abstract class Model
{
    private static string $tablePrefix = '';

    private static ?PDO $pdo = null;

    private ?PDO $pdoCurrentQuery = null;

    abstract protected function table(): string;

    public static function setTablePrefix(string $tablePrefix): void
    {
        self::$tablePrefix = $tablePrefix;
    }

    public static function setPDO(PDO $pdo): void
    {
        self::$pdo = $pdo;
    }

    public function setCurrentQueryPDO(PDO $pdo): void
    {
        $this->pdoCurrentQuery = $pdo;
    }

    public static function vm()
    {
        return new static;
    }

    private function getTableName(): string
    {
        return self::$tablePrefix . $this->table();
    }

    private function getPDO(): PDO
    {
        $pdo = $this->pdoCurrentQuery ?: self::$pdo;

        if (is_null($pdo)) {
            throw new \LogicException('Please set PDO, use method ::setPDO');
        }

        return $pdo;
    }

    public function query(string $sqlQuery, array $data = [])
    {
        $tables = array_map(fn (string $name) => "[$name]", $this->mapTable());

        $tables['[TABLE]'] = $this->getTableName();

        $sql = strtr($sqlQuery, $tables);

        $pdo = $this->getPDO();

        return new ExecuteQuery($pdo, $sql, $this, $data);
    }

    protected function hasTimestamp(): bool
    {
        return true;
    }

    protected function id(): string
    {
        return 'id';
    }

    protected function mapTable(): array
    {
        return [];
    }

    private static ?array $casts = null;

    protected function getCast(): array
    {
        return [
            // Default casting
            'created_at' => fn (mixed $timeStamp) => date('Y/m/d - H:i', strtotime($timeStamp)),
            'updated_at' => fn (mixed $timeStamp) => date('Y/m/d - H:i', strtotime($timeStamp)),
        ];
    }

    public final function castValue(string $key, mixed $value): mixed
    {
        if (is_null(self::$casts)) {
            self::$casts = $this->getCast();
        }

        $casts = self::$casts;

        if (isset($casts[$key])) {
            $fn = $casts[$key];
            return $fn($value);
        }
        return $value;
    }

    public final function getLastId(): mixed
    {
        $id = $this->id();
        return self::$pdo->lastInsertId($id);
    }

    public static function transaction(callable $callback): bool
    {
        $pdo = self::vm()->getPDO();

        try {
            $pdo->beginTransaction();
            $callback();
            $pdo->commit();
            return true;
        } catch(\Exception $e) {
            return false;
        }
    }

    public final function count(): ExecuteQuery
    {
        $idKey = $this->id();
        $sql = "SELECT count(`$idKey`) as `count` FROM [TABLE]";
        return $this->query($sql);
    }
}
