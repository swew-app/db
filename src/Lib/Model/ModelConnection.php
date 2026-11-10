<?php

declare(strict_types=1);

namespace Swew\Db\Lib\Model;

use PDO;
use Swew\Db\Model;

/** @require-extends Model */
trait ModelConnection
{
    final public function setCurrentQueryPDO(PDO $pdo): void
    {
        $this->pdoCurrentConnection = $pdo;
    }

    final public function getDriverType(): string
    {
        return (string) $this->getPDO()->getAttribute(PDO::ATTR_DRIVER_NAME);
    }

    final public function getLastId(): int|string|false
    {
        $id = $this->id();

        return $this->getPDO()->lastInsertId($id);
    }

    public static function transaction(callable $callback, bool $showException = false): bool
    {
        $pdo = self::vm()->getPDO();
        $started = false;

        try {
            $started = $pdo->beginTransaction();
            $callback();
            $pdo->commit();

            return true;
        } catch (\Throwable $e) {
            if ($started && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if ($showException) {
                throw $e;
            }

            return false;
        }
    }
}
