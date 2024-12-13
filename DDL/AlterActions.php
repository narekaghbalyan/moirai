<?php

namespace Moirai\DDL;

class AlterActions
{
    public const ADD_COLUMN = 1;
    public const MODIFY_COLUMN = 2;
    public const RENAME_COLUMN = 3;
    public const DROP_COLUMN = 4;
    public const SET_DEFAULT = 5;
    public const DROP_DEFAULT = 6;

    public const ADD_CHECK_CONSTRAINT = 7;
    public const DROP_CHECK_CONSTRAINT = 8;
    public const ADD_UNIQUE_CONSTRAINT = 9;
    public const ADD_PRIMARY_KEY_CONSTRAINT = 10;
    public const DROP_PRIMARY_KEY_CONSTRAINT = 11;
    public const ADD_FOREIGN_KEY_CONSTRAINT = 12;
    public const DROP_FOREIGN_KEY_CONSTRAINT = 13;
    public const DROP_INDEX = 14;

    public const ENABLE_KEYS = 15;
    public const DISABLE_KEYS = 16;
    public const ADD_PARTITION = 17;
    public const DROP_PARTITION = 18;
    public const ADD_COMPUTED_COLUMN = 19;
    public const DROP_COMPUTED_COLUMN = 20;
    public const LOCK_TABLE = 21;
    public const UNLOCK_TABLE = 22;
    public const RENAME_TABLE = 23;
    public const CHANGE_ENGINE = 24;
    public const CHANGE_ROW_FORMAT = 25;
    public const CHANGE_AUTO_INCREMENT = 26;
    public const CHANGE_TABLESPACE = 27;

    public const SET_STORAGE = 28;
    public const ADD_EXTENSION = 29;
    public const DROP_EXTENSION = 30;
    public const CREATE_SEQUENCE = 31;
    public const DROP_SEQUENCE = 32;
    public const RENAME_SEQUENCE = 33;
}