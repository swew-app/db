<?php

declare(strict_types=1);

namespace Swew\Db\Lib\Model;

use Swew\Db\Model;

/** @require-extends Model */
trait ModelReads
{
    final public function count(): ExecuteQuery
    {
        $idKey = $this->id();
        $sql = "SELECT count(`$idKey`) as `count` FROM [TABLE]";

        $exq = $this->query($sql);

        if ($this->softDelete()) {
            $exq->where('deleted_at', null);
        }

        return $exq;
    }

    public function select(): ExecuteQuery
    {
        $keys = '*';
        $columns = func_get_args();

        if (count($columns) > 0) {
            $keys = array_map(fn(string $key) => "`$key`", $columns);
            $keys = implode(', ', $keys);
        }

        $sql = $this->getSqlWithTableName("SELECT $keys FROM [TABLE]");

        $exq = $this->query($sql);

        if ($this->softDelete()) {
            $exq->where('deleted_at', null);
        }

        return $exq;
    }

    public function max(string $key): ExecuteQuery
    {
        $sql = $this->getSqlWithTableName("SELECT MAX(`$key`) as `$key` FROM [TABLE]");

        $exq = $this->query($sql);

        if ($this->softDelete()) {
            $exq->where('deleted_at', null);
        }

        return $exq;
    }

    public function min(string $key): ExecuteQuery
    {
        $sql = $this->getSqlWithTableName("SELECT MIN(`$key`) as `$key` FROM [TABLE]");

        $exq = $this->query($sql);

        if ($this->softDelete()) {
            $exq->where('deleted_at', null);
        }

        return $exq;
    }
}
