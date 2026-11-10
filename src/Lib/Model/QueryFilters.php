<?php

declare(strict_types=1);

namespace Swew\Db\Lib\Model;

/** @require-extends ExecuteQuery */
trait QueryFilters
{
    public function where(): self
    {
        $this->conditions->addComparison(QueryComparison::build($this->model, func_get_args()));
        return $this;
    }

    public function orWhere(): self
    {
        $this->conditions->addComparison(QueryComparison::build($this->model, func_get_args()), true);
        return $this;
    }

    public function whereIn(string $key, array $values): self
    {
        $this->conditions->addMembership($key, $values);
        return $this;
    }

    public function whereNotIn(string $key, array $values): self
    {
        $this->conditions->addMembership($key, $values, true);
        return $this;
    }
}
