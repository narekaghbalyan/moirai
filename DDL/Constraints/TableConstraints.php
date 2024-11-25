<?php

namespace Moirai\DDL\Constraints;

class TableConstraints
{
    public const CHECK = 0;
    public const UNIQUE = 1;
    public const PRIMARY_KEY = 2;
    public const FOREIGN_KEY = 3;
    public const ON_UPDATE = 4;
    public const ON_DELETE = 5;
    public const INDEX = 6;
}