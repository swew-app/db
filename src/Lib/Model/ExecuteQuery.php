<?php

declare(strict_types=1);

namespace Swew\Db\Lib\Model;

use PDO;
use PDOStatement;
use Psr\SimpleCache\CacheInterface;
use Swew\Db\Model;
use Swew\Db\Utils\Cache;
use Swew\Db\Utils\Obj;

class ExecuteQuery
{
    use QueryBatch;
    use QueryFilters;
    use QueryModifiers;
    use QueryResults;

    private PDOStatement $sth;

    private array $data = [];

    private bool $isDone = false;

    private QueryConditions $conditions;

    private QueryOptions $options;

    /** @var array{hasCache: bool, key: string, expiredSeconds: int} */
    private array $cacheConfig = [
        'hasCache' => false,
        'key' => '',
        'expiredSeconds' => 0,
    ];

    public function __construct(
        private readonly PDO $pdo,
        private string $sql,
        private readonly Model $model,
        private readonly ?CacheInterface $cache = null,
    ) {
        $this->conditions = new QueryConditions();
        $this->options = new QueryOptions();
    }

    public function __clone(): void
    {
        $this->conditions = clone $this->conditions;
        $this->options = clone $this->options;
    }

    public function id(): mixed
    {
        return $this->model->getLastId();
    }

    public function isDone(): bool
    {
        return $this->isDone;
    }

    public function exec(?array $data = null): self
    {
        $this->prepareAndExecute($data);

        return $this;
    }

    public function setData(array|Model $data): self
    {
        if ($data instanceof Model) {
            $data = $this->model->getFilteredDataWithoutId(Obj::getObjectVars($data));
        }

        $this->data = $data;

        return $this;
    }

    private function fillDto(array $value): Model
    {
        $model = clone $this->model;

        foreach ($value as $key => $val) {
            if (is_string($key) && property_exists($model, $key)) {
                $model->$key = $val;
            }
        }

        return $model;
    }

    private function prepareAndExecute(?array $data = null): bool
    {
        ['sql' => $sql, 'data' => $castedData] = $this->toSql($data);

        $sth = $this->pdo->prepare($sql);

        if ($sth === false) {
            throw new \Exception('PDO prepare exception: ' . $this->pdo->errorInfo()[2], $this->pdo->errorInfo()[1]);
        }

        $this->sth = $sth;

        $this->bindValues($this->sth, $castedData);

        return $this->isDone = $this->sth->execute();
    }

    /**
     * Bind values to their parameters in the given statement.
     */
    private function bindValues(PDOStatement $statement, array $bindings): void
    {
        foreach ($bindings as $key => $value) {
            $statement->bindValue(is_string($key) ? $key : $key + 1, $value, match (true) {
                is_null($value) => PDO::PARAM_NULL,
                is_bool($value) => PDO::PARAM_BOOL,
                is_int($value) => PDO::PARAM_INT,
                is_resource($value) => PDO::PARAM_LOB,
                default => PDO::PARAM_STR,
            });
        }
    }

    private function fetchCached(string $fetchMode, callable $callback): mixed
    {
        $cache = $this->cache;
        if ($this->cacheConfig['hasCache'] && $cache !== null) {
            ['sql' => $sql, 'data' => $data] = $this->toSql($this->data);
            $this->cacheConfig['key'] = Cache::makeKey($sql, $data, $fetchMode);
            return Cache::remember($cache, $this->cacheConfig['key'], $callback, $this->cacheConfig['expiredSeconds']);
        }
        return $callback();
    }
}
