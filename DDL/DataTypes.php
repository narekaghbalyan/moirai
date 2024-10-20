<?php

namespace Moirai\DDL;

final class DataTypes
{
    public const NULL = 1;
    public const BOOLEAN = 2;
    public const BIT = 3;

    /**
     * Number types
     */
    public const TINY_INTEGER = 4;
    public const SMALL_INTEGER = 5;
    public const MEDIUM_INTEGER = 6;
    public const INTEGER = 7;
    public const BIG_INTEGER = 8;
    public const FLOAT = 9;
    public const BINARY_FLOAT = 10;
    public const DOUBLE = 11;
    public const BINARY_DOUBLE = 12;
    public const DECIMAL = 13;
    public const REAL = 14;
    public const NUMERIC = 15;
    public const MONEY = 16;
    public const SMALL_MONEY = 17;
    public const NUMBER = 18;

    /**
     * String types
     */
    public const CHAR = 19;
    public const N_CHAR = 20;
    public const VARCHAR = 21;
    public const VARCHAR_2 = 22;
    public const N_VARCHAR = 23;
    public const N_VARCHAR_2 = 24;
    public const TINY_TEXT = 25;
    public const TEXT = 25;
    public const MEDIUM_TEXT = 25;
    public const LONG_TEXT = 25;
    public const N_TEXT = 26;
    public const TINY_BLOB = 27;
    public const BLOB = 27;
    public const MEDIUM_BLOB = 27;
    public const LONG_BLOB = 27;
    public const SET = 28;
    public const JSON = 29;
    public const JSONB = 30;
    public const BINARY = 31;
    public const VARBINARY = 32;
    public const ENUM = 33;
    public const UUID = 34;
    public const XML = 35;
    public const IMAGE = 36;
    public const SQL_VARIANT = 37;
    public const ROW_VERSION = 38;
    public const CLOB = 39;
    public const N_CLOB = 40;
    public const RAW = 41;
    public const LONG = 42;
    public const UROWID = 43;
    public const BYTEA = 44;
    public const ARRAY = 45;
    public const HSTORE = 46;
    public const INET = 47;
    public const CIDR = 48;

    /**
     * Date types
     */
    public const DATE = 49;
    public const DATE_TIME = 50;
    public const DATE_TIME_2 = 51;
    public const SMALL_DATE_TIME = 52;
    public const DATE_TIME_OFFSET = 53;
    public const TIME = 54;
    public const TIMESTAMP = 55;
    public const YEAR = 56;
    public const INTERVAL = 57;
    public const TIME_TZ = 58;
    public const TIMESTAMP_TZ = 59;
    public const TIMESTAMP_LTZ = 60;
    public const INTERVAL_YEAR_TO_MONTH = 61;
    public const INTERVAL_DAY_TO_SECOND = 62;


    /**
     * Geometry types
     */
    public const GEOMETRY = 63;
    public const GEOMETRY_COLLECTION = 64;
    public const POINT = 65;
    public const MULTI_POINT = 66;
    public const LINE = 67;
    public const LINE_STRING = 68;
    public const MULTI_LINE_STRING = 69;
    public const POLYGON = 70;
    public const MULTI_POLYGON = 71;
    public const GEOGRAPHY = 72;
    public const HIERARYCHYID = 73;
    public const LSEG = 74;
    public const BOX = 75;
    public const CIRCLE = 76;
}
