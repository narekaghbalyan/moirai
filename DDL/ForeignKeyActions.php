<?php

namespace Moirai\DDL;

class ForeignKeyActions
{
    public const CASCADE = 'CASCADE';
    public const SET_NULL = 'SET NULL';
    public const RESTRICT = 'RESTRICT';
    public const SET_DEFAULT = 'SET DEFAULT';
    public const NO_ACTION = 'NO ACTION';
}