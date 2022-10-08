<?php

declare(strict_types=1);

namespace Swew\Db\Lib;

enum ColumnType
{
    case NUMBER;
    case STRING;
    case DATE;
    case BLOB;
    case JSON;
}
