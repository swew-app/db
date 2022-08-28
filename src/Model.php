<?php

declare(strict_types=1);

namespace Swew\Db;

use LogicException;
use PDO;
use Swew\Db\Lib\Model\ExecuteQuery;
use Swew\Db\Utils\Obj;

abstract class Model
{
    private static string $tablePrefix = '';

    private static ?PDO $pdo = null;

    private ?PDO $pdoCurrentConnection = null;

    abstract protected function table(): string;

    final public static function setTablePrefix(string $tablePrefix): void
    {
        self::$tablePrefix = $tablePrefix;
    }

    final public static function setPDO(PDO $pdo): void
    {
        self::$pdo = $pdo;
    }

    final public function setCurrentQueryPDO(PDO $pdo): void
    {
        $this->pdoCurrentConnection = $pdo;
    }

    public static function vm(): static
    {
        /** @phpstan-ignore-next-line */
        return new static;
    }

    private function getTableName(): string
    {
        return self::$tablePrefix.$this->table();
    }

    private function getPDO(): PDO
    {
        $pdo = $this->pdoCurrentConnection ?: self::$pdo;

        if (is_null($pdo)) {
            throw new LogicException('Please set PDO, use method ::setPDO');
        }

        return $pdo;
    }

    public function getSqlWithTableName(string $sqlQuery): string
    {
        $tables = array_map(fn (string $name) => "[$name]", $this->mapTable());

        $tables['[TABLE]'] = $this->getTableName();

        return strtr($sqlQuery, $tables);
    }

    public function query(string $sqlQuery, array|Model $data = []): ExecuteQuery
    {
        $pdo = $this->getPDO();
        $sql = $this->getSqlWithTableName($sqlQuery);

        $exq = new ExecuteQuery($pdo, $sql, $this);
        $exq->setData($data);

        return $exq;
    }

    final public function count(): ExecuteQuery
    {
        $idKey = $this->id();
        $sql = "SELECT count(`$idKey`) as `count` FROM [TABLE]";

        return $this->query($sql);
    }

    protected function hasTimestamp(): bool
    {
        return true;
    }

    protected function id(): string
    {
        return 'id';
    }

    public function getFilteredDataWithoutId(array $data): array
    {
        $id = $this->id();

        return array_filter($data, fn ($key) => ($key !== $id), ARRAY_FILTER_USE_KEY);
    }

    /**
     * Возвращаем массив где ключ это алиас,
     * который нужно заменить на название таблицы
     *
     * @example
     * [
     *   'TABLE' => 'users',
     *   'T1'    => 'comments',
     * ]
     */
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

    protected function setCast(): array
    {
        return [];
    }

    final public function castGetValue(string $key, mixed $value): mixed
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

    final public function castSetValues(array $values): array
    {
        $casts = $this->setCast();

        foreach ($values as $key => $value) {
            if (isset($casts[$key])) {
                $fn = $casts[$key];
                $values[$key] = $fn($value);
            }
        }

        return $values;
    }

    final public function getLastId(): mixed
    {
        $id = $this->id();
        if (is_null(self::$pdo)) {
            throw new LogicException();
        }

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
        } catch (\Exception $e) {
            return false;
        }
    }

    public function select(): ExecuteQuery
    {
        $keys = '*';
        $columns = func_get_args();

        if (count($columns) > 0) {
            $keys = array_map(fn (string $key) => "`$key`", $columns);
            $keys = implode(', ', $keys);
        }

        $sql = $this->getSqlWithTableName("SELECT $keys FROM [TABLE]");

        return $this->query($sql);
    }

    public function save(): ExecuteQuery
    {
        $data = $this->getFilteredDataWithoutId(Obj::getObjectVars($this));

        $keys = array_map(fn (string $key) => "`$key`", array_keys($data));
        $keysString = implode(', ', $keys);

        $valuePlaces = array_fill(0, count($keys), '?');
        $valueString = implode(', ', $valuePlaces);

        $sqlQuery = "INSERT INTO [TABLE] ($keysString) VALUES ($valueString)";
        $sql = $this->getSqlWithTableName($sqlQuery);

        $pdo = $this->getPDO();
        $exq = new ExecuteQuery($pdo, $sql, $this);

        return $exq->exec(array_values($data));
    }

    public function update(array|Model|null $data = null): ExecuteQuery
    {
        if (is_null($data)) {
            $data = $this;
        }

        if (! is_array($data)) {
            $data = $this->getFilteredDataWithoutId(Obj::getObjectVars($data));
        } else {
            $data = $this->getFilteredDataWithoutId($data);
        }

        $data = $this->castSetValues($data);

        $keys = array_keys($data);
        $mappedKeys = array_map(fn (string $key) => "`$key` = ?", $keys);

        $sql = 'UPDATE [TABLE] SET '.implode(', ', $mappedKeys);
        $sql = $this->getSqlWithTableName($sql);

        $exq = new ExecuteQuery($this->getPDO(), $sql, $this);

        $exq->setData(array_values($data));

        return $exq;
    }
}
