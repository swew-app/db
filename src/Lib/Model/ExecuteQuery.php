<?php

declare(strict_types=1);

namespace Swew\Db\Lib\Model;

use PDO;
use PDOStatement;
use Swew\Db\Model;
use Swew\Db\Utils\Obj;

class ExecuteQuery
{
    private PDOStatement $sth;

    public function __construct(
        readonly private PDO $pdo,
        private string $sql,
        readonly private Model $dto,
        private array $data = []
    ) {
    }

    public function id(): mixed
    {
        return $this->dto->getLastId();
    }

    public function exec(?array $data = null): self
    {
        $this->prepareAndExecute($data);

        return $this;
    }

    public function execMany(?array $data = null): bool
    {
        [ 'sql'=> $sql, 'data' => $newData ] = $this->toSql($data ?: $this->data);

        try {
            $this->pdo->beginTransaction();
            $sth = $this->pdo->prepare($sql);

            foreach ($newData as $value) {
                $sth->execute(
                    $this->dto->castSetValues($value)
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

        $this->data = $this->dto->getFilteredDataWithoutId($data);

        return $this;
    }

    public function get(): array|bool
    {
        $this->prepareAndExecute();

        return $this->sth->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getFirst(): array
    {
        $this->limit(1);

        $this->prepareAndExecute();

        return $this->sth->fetch(PDO::FETCH_ASSOC);
    }

    public function getFirstItem(): Model
    {
        $result = $this->getFirst();

        return $this->fillDto($result);
    }

    public function getItems()
    {
        $results = $this->get();

        return array_map(
            fn ($data) => $this->fillDto($data),
            $results
        );
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

    public function toSql(?array $data = null): array
    {
        $data = $data ?: $this->data ?: [];
        $sql = $this->sql;
        $where = [];

        if (count($this->where) > 0) {
            foreach ($this->where as $v) {
                $where[] = $v[0];
                $data[] = $v[1];
            }
            $where = [
                implode(' AND ', $where)
            ];
        }

        if (count($this->orWhere) > 0) {
            foreach ($this->orWhere as $v) {
                $where[] = $v[0];
                $data[] = $v[1];
            }
        }

        $sql .= ' ' . implode(' OR ', $where);

        if ($this->limit > 0) {
            if ($this->offset > 0) {
                $sql .= "LIMIT " . $this->offset . ', '. $this->limit;
            } else {
                $sql .= "LIMIT " . $this->limit;
            }
        }

        return [
            'sql' => $sql,
            'data' => $this->dto->castSetValues($data),
        ];
    }

    private array $where = [];

    private array $orWhere = [];

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

        return ["WHERE `$key` $comp ?", $val];
    }

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

    private function fillDto(array $value): Model
    {
        $dto = clone $this->dto;

        foreach ($value as $key => $value) {
            if (isset($dto->$key)) {
                $dto->$key = $this->dto->castGetValue($key, $value);
            }
        }

        return $dto;
    }

    private function prepareAndExecute(?array $data = null): bool
    {
        [ 'sql'=> $sql, 'data' => $newData ] = $this->toSql($data ?: $this->data);

        $this->sth = $this->pdo->prepare($sql);
        return $this->sth->execute($newData);
    }
}
