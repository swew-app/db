<?php

declare(strict_types=1);

namespace Swew\Db\Lib\Model;

use Swew\Db\Model;

/** @require-extends Model */
trait ModelCasting
{
    /** @var array{to: array<string, callable>, from: array<string, callable>}|null */
    private ?array $castRules = null;

    /** @return array{to?: array<string, callable>, from?: array<string, callable>} */
    protected function cast(): array
    {
        return [];
    }

    /** @return array{to: array<string, callable>, from: array<string, callable>} */
    private function getCast(): array
    {
        if (is_null($this->castRules)) {
            $defaultCasts = [
                'to' => [
                    'created_at' => fn(int|string|null $timeStamp) => $timeStamp
                        ? date('Y.m.d - H:i:s', (int) $timeStamp)
                        : '',
                    'updated_at' => fn(int|string|null $timeStamp) => $timeStamp
                        ? date('Y.m.d - H:i:s', (int) $timeStamp)
                        : '',
                ],
                'from' => [
                    'created_at' => fn(int|string|null $timeStamp) => $timeStamp
                        ? date('Y.m.d - H:i:s', (int) $timeStamp)
                        : '',
                    'updated_at' => fn(int|string|null $timeStamp) => $timeStamp
                        ? date('Y.m.d - H:i:s', (int) $timeStamp)
                        : '',
                ],
            ];

            $casts = $this->cast();
            $this->castRules = [
                'to' => array_replace($defaultCasts['to'], $casts['to'] ?? []),
                'from' => array_replace($defaultCasts['from'], $casts['from'] ?? []),
            ];
        }

        return $this->castRules;
    }

    final public function castSetValue(string $key, mixed $value): mixed
    {
        $cast = $this->getCast()['to'][$key] ?? null;

        if (is_null($cast)) {
            return $value;
        }
        return $cast($value);
    }

    final public function castSetValues(array $values): array
    {
        $casts = $this->getCast()['to'];

        foreach ($values as $key => $value) {
            if (isset($casts[$key])) {
                $fn = $casts[$key];
                $values[$key] = $fn($value);
            }
        }

        return $values;
    }

    final public function castValues(array $values): array
    {
        $casts = $this->getCast()['from'];

        foreach ($values as $key => $value) {
            if (isset($casts[$key])) {
                $fn = $casts[$key];
                $values[$key] = $fn($value);
            }
        }

        return $values;
    }
}
