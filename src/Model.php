<?php

declare(strict_types=1);

namespace Swew\Db;

use PDO;
use Psr\SimpleCache\CacheInterface;
use Swew\Db\Lib\Model\ModelCasting;
use Swew\Db\Lib\Model\ModelConnection;
use Swew\Db\Lib\Model\ModelQueries;
use Swew\Db\Lib\Model\ModelReads;
use Swew\Db\Lib\Model\ModelWrites;

abstract class Model
{
    use ModelConnection;
    use ModelQueries;
    use ModelReads;
    use ModelWrites;
    use ModelCasting;

    private ?PDO $pdoCurrentConnection = null;

    private function getPDO(): PDO
    {
        return $this->pdoCurrentConnection ?? ModelConfig::getPDO();
    }

    abstract protected function table(): string;

    public static function vm(): static
    {
        return new static();
    }

    protected function getCache(): CacheInterface|bool|null
    {
        return null;
    }

    final public function getTableName(): string
    {
        return ModelConfig::getTablePrefix() . $this->table();
    }

    protected function hasTimestamp(): bool
    {
        return false;
    }

    protected function softDelete(): bool
    {
        return false;
    }

    protected function id(): string
    {
        return 'id';
    }

    /**
     * Возвращаем массив где ключ это алиас,
     * который нужно заменить на название таблицы
     *
     * @return array<string, string|Model>
     * @example
     * [
     *   'TABLE' => 'users',
     *   'T1'    => 'comments',
     * ]
     */
    protected function mapTable(): array
    {
        return [];
    }
}
