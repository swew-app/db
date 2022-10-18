<?php

declare(strict_types=1);

namespace Swew\Db\Lib;

use Swew\Db\Utils\Str;

class MigrationColumn
{
    private bool $isNullable = false;

    private bool $isUnique = false;

    private bool $hasIndex = false;

    private bool $hasFulltext = false;

    private string $type = '';

    private string $defaultValue = '';

    private string $suffix = '';

    public function __construct(
        private readonly string $columnName
    ) {
        // TODO: validate $columnName
    }

    public function toString(): string
    {
        $str = [];

        $str[] = "`{$this->columnName}`";
        $str[] = $this->type;

        if (! empty($this->suffix)) {
            $str[] = $this->suffix;
        }

        if (! empty($this->defaultValue)) {
            $str[] = $this->defaultValue;
        }

        if ($this->isUnique) {
            $str[] = 'UNIQUE';
        }

        if ($this->hasIndex) {
            $str[] = 'INDEX';
        }
        if ($this->hasFulltext) {
            $str[] = 'FULLTEXT';
        }

        if (! $this->isNullable) {
            $str[] = 'NOT NULL';
        }

        return implode(' ', $str);
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function setSuffix(string $suffix): void
    {
        $this->suffix = $suffix;
    }

    public function nullable(): self
    {
        $this->isNullable = true;

        return $this;
    }

    public function unique(): self
    {
        $this->isUnique = true;

        return $this;
    }

    public function default(mixed $value): self
    {
        $str = Str::valueToString($value);
        $this->defaultValue = "DEFAULT($str)";

        return $this;
    }

    public function index(): self
    {
        // TODO: create ALTER command
//        $this->hasIndex = true;
        return $this;
    }

    public function fulltext(): self
    {
        $this->hasFulltext = true;

        return $this;
    }
}
