<?php

declare(strict_types=1);

namespace Swew\Db\Lib\Model;

use LogicException;

/** @internal */
final class QueryOptions
{
    private int $limit = 0;

    private int $offset = 0;

    /** @var list<string> */
    private array $orderBy = [];

    /** @var list<string> */
    private array $groupBy = [];

    public function limit(int $limit, int $offset = 0): self
    {
        $this->limit = $limit;

        if ($offset > 0) {
            $this->offset($offset);
        }

        return $this;
    }

    public function offset(int $offset): self
    {
        $this->offset = $offset;

        return $this;
    }

    public function orderBy(string $key, string $direction = 'ASC'): self
    {
        if (!in_array($direction, ['ASC', 'DESC'], true)) {
            throw new LogicException('Wrong orderBy direction, use ASC or DESC');
        }

        $this->orderBy[] = "`$key` $direction";

        return $this;
    }

    public function groupBy(string $key): self
    {
        $this->groupBy[] = "`$key`";

        return $this;
    }

    public function compile(): string
    {
        $sql = '';
        if ($this->groupBy !== []) {
            $sql .= ' GROUP BY ' . implode(', ', $this->groupBy);
        }
        if ($this->orderBy !== []) {
            $sql .= ' ORDER BY ' . implode(', ', $this->orderBy);
        }
        if ($this->limit > 0) {
            $sql .= ' LIMIT ' . ($this->offset > 0 ? $this->offset . ', ' : '') . $this->limit;
        }
        return $sql;
    }
}
