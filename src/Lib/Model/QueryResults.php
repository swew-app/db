<?php

declare(strict_types=1);

namespace Swew\Db\Lib\Model;

use LogicException;
use PDO;
use Swew\Db\Model;

/** @require-extends ExecuteQuery */
trait QueryResults
{
    public function getMap(callable $fn): array|bool
    {
        $res = $this->get();

        if (is_bool($res)) {
            return $res;
        }

        return array_map($fn, $res);
    }

    /** @return array<array-key, array<array-key, mixed>>|false */
    public function get(): array|bool
    {
        $self = $this;

        $callback = function () use (&$self) {
            $self->prepareAndExecute();

            return $self->sth->fetchAll(PDO::FETCH_ASSOC);
        };

        $results = $this->fetchCached('all', $callback);

        if (!is_array($results)) {
            throw new LogicException('Cached query result must be an array');
        }

        return array_map(fn(array $v) => $this->model->castValues($v), $results);
    }

    /** @return array<array-key, mixed>|false */
    public function getFirst(): array|bool
    {
        $this->limit(1);
        $self = $this;

        $callback = function () use (&$self) {
            $self->prepareAndExecute();

            // TODO: получать из модели
            return $self->sth->fetch(PDO::FETCH_ASSOC);
        };

        $result = $this->fetchCached('first', $callback);

        if (!$result) {
            return false;
        }

        if (!is_array($result)) {
            throw new LogicException('Cached query row must be an array');
        }

        return $this->model->castValues($result);
    }

    public function getFirstItem(): ?Model
    {
        $result = $this->getFirst();

        if (!is_array($result)) {
            return null;
        }

        return $this->fillDto($result);
    }

    public function getItems(): array
    {
        $results = $this->get();

        if (!is_array($results)) {
            throw new LogicException('Wrong query for get result');
        }

        return array_map(fn(array $data) => $this->fillDto($data), $results);
    }

    public function getValue(?string $key = null): mixed
    {
        $result = $this->getFirst();

        if (!is_array($result)) {
            return null;
        }

        if (!is_null($key)) {
            return $result[$key];
        }

        return array_values($result)[0];
    }
}
