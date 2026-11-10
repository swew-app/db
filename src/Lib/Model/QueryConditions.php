<?php

declare(strict_types=1);

namespace Swew\Db\Lib\Model;

/** @internal */
final class QueryConditions
{
    /** @var array<string, list<string>> */
    private array $sql = ['where' => [], 'orWhere' => [], 'in' => [], 'notIn' => []];

    /** @var array<string, list<mixed>> */
    private array $bindings = ['where' => [], 'orWhere' => [], 'in' => [], 'notIn' => []];

    /** @param array{0: string, 1?: mixed} $condition */
    public function addComparison(array $condition, bool $or = false): void
    {
        $group = $or ? 'orWhere' : 'where';
        $this->sql[$group][] = $condition[0];
        if (array_key_exists(1, $condition)) {
            $this->bindings[$group][] = $condition[1];
        }
    }

    public function addMembership(string $key, array $values, bool $not = false): void
    {
        if ($not && $values === []) {
            return;
        }
        $group = $not ? 'notIn' : 'in';
        $operator = $not ? 'NOT IN' : 'IN';
        $places = implode(', ', array_fill(0, count($values), '?'));
        $this->sql[$group][] = $values === [] ? '0 = 1' : "`$key` $operator ($places)";
        $this->bindings[$group] = array_merge($this->bindings[$group], array_values($values));
    }

    public function compile(array &$data): string
    {
        $where = $this->sql['where'];
        if ($this->sql['orWhere'] !== []) {
            $where[] = '(' . implode(' OR ', $this->sql['orWhere']) . ')';
        }
        $where = array_merge($where, $this->sql['in'], $this->sql['notIn']);

        foreach (['where', 'orWhere', 'in', 'notIn'] as $group) {
            foreach ($this->bindings[$group] as $value) {
                $data[] = $value;
            }
        }

        return $where === [] ? '' : ' WHERE (' . implode(' AND ', $where) . ')';
    }
}
