<?php

namespace Moirai\DDL;

final class DataTypes
{
    /**
     * Integer types
     */
    public const TINY_INTEGER = 'tinyInteger';
    public const SMALL_INTEGER = 'smallInteger';
    public const MEDIUM_INTEGER = 'mediumInteger';
    public const INTEGER = 'integer';
    public const BIG_INTEGER = 'bigInteger';

    /**
     * Float types
     */
    public const FLOAT = 'float';
    public const DOUBLE = 'double';
    public const DECIMAL = 'decimal';

    /**
     * String types
     */
    public const CHAR = 'char';
    public const VARCHAR = 'string';
    public const TINY_TEXT = 'tinyText';
    public const TEXT = 'text';
    public const MEDIUM_TEXT = 'mediumText';
    public const LONG_TEXT = 'longText';
    public const TINY_BLOB = 'tinyblob';
    public const BLOB = 'blob';
    public const MEDIUM_BLOB = 'mediumBlob';
    public const LONG_BLOB = 'longBlob';
    public const SET = 'set';
    public const JSON = 'json';
    public const JSONB = 'jsonb';
    public const BINARY = 'binary';
    public const VARBINARY = 'varbinary';
    public const ENUM = 'enum';
    public const IP_V4 = 'ipv4';
    public const IP_V6 = 'ipv6';
    public const ROW = 'row';
    public const UUID = 'uuid';

    /**
     * Logical types
     */
    public const BOOLEAN = 'boolean';

    /**
     * Date types
     */
    public const DATE = 'date';
    public const DATE_TIME = 'dateTime';
    public const TIME = 'time';
    public const TIMESTAMP = 'timestamp';
    public const YEAR = 'year';

    /**
     * Bit type
     */
    public const BIT = 'bit';

    /**
     * Geometry types
     */
    public const GEOMETRY = 'geometry';
    public const GEOMETRY_COLLECTION = 'geometryCollection';
    public const POINT = 'point';
    public const MULTI_POINT = 'multipoint';
    public const LINE_STRING = 'lineString';
    public const MULTI_LINE_STRING = 'multiLineString';
    public const POLYGON = 'polygon';
    public const MULTI_POLYGON = 'multiPolygon';







    public const DATE_TIME_TZ = null; // ?
    public const TIME_TZ = null; // ?
    public const TIMESTAMP_TZ = null; // ?
    public const IP_ADDRESS = null;
    public const MAC_ADDRESS = null;
    public const COMPUTED = null;
    public const TABLE_COMMENT = null; // ?
}
