<?php

declare(strict_types=1);

namespace Swew\Db\Lib\Model;

use LogicException;

/** @require-extends ExecuteQuery */
trait QueryModifiers
{
    public function cache(int $seconds = 0): self
    {
        if (is_null($this->cache)) {
            throw new LogicException('Set getCache method in "' . get_class($this->model) . '"');
        }

        $this->cacheConfig['expiredSeconds'] = $seconds;
        $this->cacheConfig['hasCache'] = true;

        return $this;
    }

    /** @return array{sql: string, data: array<array-key, mixed>} */
    public function toSql(?array $data = null): array
    {
        $data ??= $this->data;
        $sql = $this->sql . $this->conditions->compile($data) . $this->options->compile();
        return ['sql' => $sql, 'data' => $data];
    }

    public function limit(int $limit, int $offset = 0): self
    {
        $this->options->limit($limit, $offset);
        return $this;
    }

    public function offset(int $offset): self
    {
        $this->options->offset($offset);
        return $this;
    }

    public function orderBy(string $key, string $direction = 'ASC'): self
    {
        $this->options->orderBy($key, $direction);
        return $this;
    }

    public function groupBy(string $key): self
    {
        $this->options->groupBy($key);
        return $this;
    }
}
