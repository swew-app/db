<?php

declare(strict_types=1);

namespace Swew\Db\Lib;

enum ColumnSize
{
    // alias for: BIGINT UNSIGNED NOT NULL AUTO_INCREMENT UNIQUE
    case SERIAL;
    // size:    -128:128    0:255 // BOOLEAN
    case TINYINT;
    // size:    -32768:32768    0:65535
    case SMALLINT;
    // size:    -8388608:8388608    0:16777215
    case MEDIUMINT;
    // size:    -2147483648:2147483648  0:4294967295
    case INT;
    // size:    -2^63:2^63-1    0:2^64-1
    case BIGINT;
    //
    case DECIMAL;
    case DOUBLE;
    case FLOAT;
    // range: 1:64
    case BIT;
    //
    // DATE
    //
    // "Zero" Value: '0000-00-00' range is '1000-01-01' to '9999-12-31'
    case DATE;
    // "Zero" Value: '00:00:00' range from '-838:59:59' to '838:59:59'
    case TIME;
    // "Zero" Value: '0000-00-00 00:00:00'
    case DATETIME;
    // "Zero" Value: '0000-00-00 00:00:00'
    case TIMESTAMP;
    // "Zero" Value: 0000
    case YEAR;
    //
    // String
    //
    // length range from 0 to 255
    case CHAR;
    // length range from 0 to 65,535
    case VARCHAR;
    // default 1  characters
    case BINARY;
    case VARBINARY;
    case TINYBLOB;
    // max: 2^16−1  characters
    case BLOB;
    case MEDIUMBLOB;
    case LONGBLOB;
    // max: 255  characters
    case TINYTEXT;
    case TEXT;
    case MEDIUMTEXT;
    case LONGTEXT;
    // max: 2^16−1  characters
    case ENUM;
}
