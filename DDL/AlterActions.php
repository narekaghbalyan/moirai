<?php

namespace Moirai\DDL;

class AlterActions
{
    public const ADD_COLUMN = 0;
    public const ADD_COMPUTED_COLUMN = 1;
    public const DROP_COMPUTED_COLUMN = 2;
    public const MODIFY_COLUMN = 3;
    public const RENAME_COLUMN = 4;
    public const DROP_COLUMN = 5;
    public const SET_DEFAULT = 6;
    public const DROP_DEFAULT = 7;
    public const ADD_CHECK_CONSTRAINT = 8;
    public const DROP_CHECK_CONSTRAINT = 9;
    public const ADD_UNIQUE_CONSTRAINT = 10;
    public const ADD_PRIMARY_KEY_CONSTRAINT = 11;
    public const DROP_PRIMARY_KEY_CONSTRAINT = 12;
    public const ADD_FOREIGN_KEY_CONSTRAINT = 13;
    public const DROP_FOREIGN_KEY_CONSTRAINT = 14;
    public const DROP_INDEX = 15;
    public const ENABLE_KEYS = 16;
    public const DISABLE_KEYS = 17;
    public const LOCK_TABLE = 18;
    public const UNLOCK_TABLE = 19;
    public const RENAME_TABLE = 20;
    public const CHANGE_ENGINE = 21;
    public const CHANGE_ROW_FORMAT = 22;
    public const CHANGE_AUTO_INCREMENT = 23;
    public const CHANGE_TABLESPACE = 24;
    public const SET_STORAGE = 25;
    public const ADD_EXTENSION = 26;
    public const DROP_EXTENSION = 27;
    public const CREATE_SEQUENCE = 28;
    public const DROP_SEQUENCE = 29;
    public const RENAME_SEQUENCE = 30;
}