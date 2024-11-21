<?php

namespace Moirai\DDL;

class Constraints
{
    public const UNSIGNED = 0;
    public const CHECK = 1;

    public const AUTOINCREMENT = 2;

    public const NOT_NULL = 3;
    public const UNIQUE = 4;
    public const DEFAULT = 5;

    public const COLLATION = 6;
    public const CHARSET = 7;

    public const PRIMARY_KEY = 8;
    public const FOREIGN_KEY = 9;
    public const ON_UPDATE = 10;
    public const ON_DELETE = 11;

    public const INVISIBLE = 12;

    public const INDEX = 13;

    public const COMMENT = 14;
}