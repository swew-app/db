<?php

declare(strict_types=1);

namespace Swew\Db\Lib\Model;

use LogicException;

/** @require-extends ExecuteQuery */
trait QueryBatch
{
    /** @param array<array-key, array<array-key, mixed>>|null $data */
    public function execMany(?array $data = null): bool
    {
        ['sql' => $sql] = $this->toSql([]);
        $started = false;
        $previousBindings = null;

        try {
            $started = $this->pdo->beginTransaction();
            $sth = $this->pdo->prepare($sql);
            if ($sth === false) {
                throw new LogicException('Unable to prepare batch query');
            }

            foreach ($data ?? $this->data as $value) {
                if (!is_array($value)) {
                    throw new LogicException('Batch rows must be arrays');
                }
                ['data' => $bindings] = $this->toSql($this->model->castSetValues($value));
                if (
                    isset($previousBindings)
                    && (
                        array_diff_key($bindings, $previousBindings) !== []
                        || array_diff_key($previousBindings, $bindings) !== []
                    )
                ) {
                    throw new LogicException('Batch rows must have the same parameters');
                }
                $previousBindings = $bindings;
                $this->bindValues($sth, $bindings);

                if (!$sth->execute()) {
                    throw new \PDOException($sth->errorInfo()[2]);
                }
            }

            $this->pdo->commit();

            return true;
        } catch (\Throwable $e) {
            if ($started && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            return false;
        }
    }
}
