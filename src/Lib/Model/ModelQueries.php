<?php

declare(strict_types=1);

namespace Swew\Db\Lib\Model;

use Swew\Db\Model;
use Swew\Db\ModelConfig;

/** @require-extends Model */
trait ModelQueries
{
    public function getSqlWithTableName(string $sqlQuery): string
    {
        $tables = [];
        foreach ($this->mapTable() as $alias => $table) {
            if (is_string($table) && is_subclass_of($table, Model::class)) {
                $table = $table::vm();
            }
            $tables["[$alias]"] = $table instanceof Model ? $table->getTableName() : $table;
        }

        $tables['[TABLE]'] = $this->getTableName();

        return strtr($sqlQuery, $tables);
    }

    public function query(string $sqlQuery, array|Model $data = []): ExecuteQuery
    {
        $pdo = $this->getPDO();
        $sql = $this->getSqlWithTableName($sqlQuery);
        $cache = $this->getCache();

        if (is_bool($cache)) {
            if ($cache === true && ModelConfig::hasDefaultCache()) {
                $cache = ModelConfig::getDefaultCache();
            } else {
                $cache = null;
            }
        }

        $exq = new ExecuteQuery($pdo, $sql, $this, $cache);
        $exq->setData($data);

        return $exq;
    }
}
