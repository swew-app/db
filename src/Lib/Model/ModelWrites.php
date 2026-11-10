<?php

declare(strict_types=1);

namespace Swew\Db\Lib\Model;

use Swew\Db\Model;
use Swew\Db\Utils\Obj;

/** @require-extends Model */
trait ModelWrites
{
    public function getFilteredDataWithoutId(array $data): array
    {
        $id = $this->id();

        return array_filter($data, fn($key) => $key !== $id, ARRAY_FILTER_USE_KEY);
    }

    /** @param array<array-key, array<array-key, mixed>> $dataList */
    public function insertMany(array $dataList): void
    {
        foreach ($dataList as $data) {
            $this->save($data);
        }
    }

    public function insert(?array $data = null): ExecuteQuery
    {
        return $this->save($data);
    }

    public function save(?array $data = null): ExecuteQuery
    {
        if (is_null($data)) {
            $data = $this->getFilteredDataWithoutId(Obj::getObjectVars($this));
        }

        $data = $this->castSetValues($data);

        if ($this->hasTimestamp()) {
            $this->addTimestampValues($data, true);
        }

        $keys = array_map(fn(int|string $key) => "`$key`", array_keys($data));
        $keysString = implode(', ', $keys);

        $valuePlaces = array_fill(0, count($keys), '?');
        $valueString = implode(', ', $valuePlaces);

        $sqlQuery = "INSERT INTO [TABLE] ($keysString) VALUES ($valueString)";
        $sql = $this->getSqlWithTableName($sqlQuery);

        $pdo = $this->getPDO();
        $exq = new ExecuteQuery($pdo, $sql, $this);

        return $exq->exec(array_values($data));
    }

    public function update(array|Model|null $data = null): ExecuteQuery
    {
        if (is_null($data)) {
            $data = $this;
        }

        if (!is_array($data)) {
            $data = $this->getFilteredDataWithoutId(Obj::getObjectVars($data));
        } else {
            $data = $this->getFilteredDataWithoutId($data);
        }

        $data = $this->castSetValues($data);

        if ($this->hasTimestamp()) {
            $this->addTimestampValues($data, false);
        }

        $keys = array_keys($data);
        $mappedKeys = array_map(fn(int|string $key) => "`$key` = ?", $keys);

        $sql = 'UPDATE [TABLE] SET ' . implode(', ', $mappedKeys);
        $sql = $this->getSqlWithTableName($sql);

        $exq = new ExecuteQuery($this->getPDO(), $sql, $this);

        $exq->setData(array_values($data));

        return $exq;
    }

    public function delete(): ExecuteQuery
    {
        $sql = 'DELETE FROM [TABLE]';

        if ($this->softDelete()) {
            return $this->update([
                'deleted_at' => time(),
            ]);
        }

        $sql = $this->getSqlWithTableName($sql);

        return new ExecuteQuery($this->getPDO(), $sql, $this);
    }

    private function addTimestampValues(array &$data, bool $isCreate): void
    {
        $now = time();

        $data['updated_at'] = $now;

        if ($isCreate) {
            $data['created_at'] = $now;
        }
    }
}
