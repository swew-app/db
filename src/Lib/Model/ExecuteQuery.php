<?php

declare(strict_types=1);

namespace Swew\Db\Lib\Model;

use LogicException;
use PDO;
use PDOStatement;
use Psr\SimpleCache\CacheInterface;
use Swew\Db\Model;
use Swew\Db\Utils\Cache;
use Swew\Db\Utils\Obj;

class ExecuteQuery
{
    private PDOStatement $sth;

    private array $data = [];

    private bool $isDone = false;

    private array $cacheConfig = [
        'hasCache' => false,
        'key' => '',
        'expiredSeconds' => 0,
    ];

    public function __construct(
        readonly private PDO $pdo,
        private string $sql,
        readonly private Model $model,
        readonly private ?CacheInterface $cache = null
    ) {
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

    public function execMany(?array $data = null): bool
    {
        ['sql' => $sql, 'data' => $castedData] = $this->toSql($data ?: $this->data);

        try {
            $this->pdo->beginTransaction();
            $sth = $this->pdo->prepare($sql);

            foreach ($castedData as $value) {
                $sth->execute(
                    $this->model->castValues($value, false)
                );
            }

            $this->pdo->commit();

            return true;
        } catch (\Exception $e) {
            $this->pdo->rollBack();

            return false;
        }
    }

    public function setData(array|Model $data): self
    {
        if ($data instanceof Model) {
            $data = Obj::getObjectVars($data);
        }

        $this->data = $this->model->getFilteredDataWithoutId($data);

        return $this;
    }

    public function getMap(callable $fn): array|bool
    {
        $res = $this->get();

        if (is_bool($res)) {
            return $res;
        }

        return array_map($fn, $res);
    }

    public function get(): array|bool
    {
        $self = $this;

        $callback = function () use (&$self) {
            $self->prepareAndExecute();

            return $self->sth->fetchAll(PDO::FETCH_ASSOC);
        };

        if ($this->cacheConfig['hasCache'] && ! is_null($this->cache)) {
            $this->createCacheKey();

            $results = Cache::remember(
                $this->cache,
                $this->cacheConfig['key'],
                $callback,
                $this->cacheConfig['expiredSeconds']
            );
        } else {
            $results = $callback();
        }

        return array_map(
            fn (mixed $v) => $this->model->castValues($v, true),
            $results
        );
    }

    public function getFirst(): array|bool
    {
        $this->limit(1);
        $self = $this;

        $callback = function () use (&$self) {
            $self->prepareAndExecute();

            return $self->sth->fetch(PDO::FETCH_ASSOC);
        };

        if ($this->cacheConfig['hasCache'] && ! is_null($this->cache)) {
            $this->createCacheKey();

            $result = Cache::remember(
                $this->cache,
                $this->cacheConfig['key'],
                $callback,
                $this->cacheConfig['expiredSeconds']
            );
        } else {
            $result = $callback();
        }

        if (! $result) {
            return false;
        }

        return $this->model->castValues($result, true);
    }

    public function getFirstItem(): Model
    {
        $result = $this->getFirst();

        if (! is_array($result)) {
            throw new LogicException('Wrong query for get result');
        }

        return $this->fillDto($result);
    }

    public function getItems(): array
    {
        $results = $this->get();

        if (! is_array($results)) {
            throw new LogicException('Wrong query for get result');
        }

        return array_map(
            fn ($data) => $this->fillDto($data),
            $results
        );
    }

    public function getValue(?string $key = null): mixed
    {
        $result = $this->getFirst();

        if (! is_array($result)) {
            return null;
        }

        if (! is_null($key)) {
            return $result[$key];
        }

        return array_values($result)[0];
    }

    public function toSql(?array $data = null): array
    {
        $data = $data ?: $this->data ?: [];
        $sql = $this->sql;
        $where = [];
        $orWhere = [];

        if (count($this->where) > 0) {
            foreach ($this->where as $v) {
                $where[] = $v[0];
                $data[] = $v[1];
            }
        }

        if (count($this->orWhere) > 0) {
            foreach ($this->orWhere as $v) {
                $orWhere[] = $v[0];
                $data[] = $v[1];
            }

            $where[] = '('.implode(' OR ', $orWhere).')';
        }

        if (count($this->whereIn) > 0) {
            foreach ($this->whereIn as $v) {
                $valuePlaces = array_fill(0, count($v[1]), '?');
                $valuePlaces = implode(', ', $valuePlaces);
                $where[] = "`{$v[0]}` IN ($valuePlaces)";

                foreach ($v[1] as $val) {
                    $data[] = $val;
                }
            }
        }

        if (count($this->whereNotIn) > 0) {
            foreach ($this->whereNotIn as $v) {
                $valuePlaces = array_fill(0, count($v[1]), '?');
                $valuePlaces = implode(', ', $valuePlaces);
                $where[] = "`{$v[0]}` NOT IN ($valuePlaces)";

                foreach ($v[1] as $val) {
                    $data[] = $val;
                }
            }
        }

        if (count($where) > 0) {
            $where = [
                implode(' AND ', $where),
            ];

            $sql .= ' WHERE ('.implode(' AND ', $where).')';
        }

        if (count($this->orderBy) > 0) {
            $sql .= ' ORDER BY '.implode(', ', $this->orderBy);
        }

        if ($this->limit > 0) {
            if ($this->offset > 0) {
                $sql .= ' LIMIT '.$this->offset.', '.$this->limit;
            } else {
                $sql .= ' LIMIT '.$this->limit;
            }
        }

        return [
            'sql' => $sql,
            'data' => $this->model->castValues($data, false),
        ];
    }

    // region [where]

    private array $where = [];

    private array $orWhere = [];

    private array $whereIn = [];

    private array $whereNotIn = [];

    public function where(): self
    {
        $args = func_get_args();

        $this->where[] = $this->whereQuery($args);

        return $this;
    }

    public function orWhere(): self
    {
        $args = func_get_args();

        $this->orWhere[] = $this->whereQuery($args);

        return $this;
    }

    public function whereIn(string $key, array $values): self
    {
        if (count($values) === 0) {
            return $this;
        }
        $this->whereIn[] = [$key, $values];

        return $this;
    }

    public function whereNotIn(string $key, array $values): self
    {
        if (count($values) === 0) {
            return $this;
        }
        $this->whereNotIn[] = [$key, $values];

        return $this;
    }

    private function whereQuery(array $args): array
    {
        $key = '';
        $comp = '=';
        $val = '';
        $count = count($args);

        if ($count === 2) {
            $key = $args[0];
            $val = $args[1];
        } elseif ($count === 3) {
            $key = $args[0];
            $comp = $args[1];
            $val = $args[2];
        } else {
            throw new \LogicException('Wrong parameters');
        }

        if (is_null($val)) {
            $comp = 'IS ';
        }

        return ["`$key` $comp ?", $val];
    }
    // endregion

    private int $limit = 0;

    public function limit(int $limit, int $offset = 0): self
    {
        $this->limit = $limit;

        if ($offset > 0) {
            $this->offset($offset);
        }

        return $this;
    }

    private int $offset = 0;

    public function offset(int $offset): self
    {
        $this->offset = $offset;

        return $this;
    }

    private array $orderBy = [];

    public function orderBy(string $key, string $direction = 'ASC'): self
    {
        if (! in_array($direction, ['ASC', 'DESC'])) {
            throw new LogicException('Wrong orderBy direction, use ASC or DESC');
        }

        $this->orderBy[] = "`$key` $direction";

        return $this;
    }

    private function fillDto(array $value): Model
    {
        $model = clone $this->model;
        $value = $this->model->castValues($value, true);

        foreach ($value as $key => $val) {
            if (isset($model->$key)) {
                $model->$key = $val;
            }
        }

        return $model;
    }

    private function prepareAndExecute(?array $data = null): bool
    {
        ['sql' => $sql, 'data' => $castedData] = $this->toSql($data ?: $this->data);

        $this->sth = $this->pdo->prepare($sql);

        return $this->isDone = $this->sth->execute($castedData);
    }

    private function createCacheKey(): string
    {
        ['sql' => $sql, 'data' => $castedData] = $this->toSql($this->data);

        return $this->cacheConfig['key'] = Cache::makeKey($sql, $castedData);
    }

    public function cache(int $seconds = 0): self
    {
        if (is_null($this->cache)) {
            throw new LogicException('Set getCache method in "'.get_class($this->model).'"');
        }

        $this->cacheConfig['expiredSeconds'] = $seconds;
        $this->cacheConfig['hasCache'] = true;

        return $this;
    }
}
