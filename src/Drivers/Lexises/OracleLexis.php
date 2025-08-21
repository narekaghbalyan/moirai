<?php

namespace Moirai\Drivers\Lexises;

use Moirai\DDL\Shared\AlterActions;
use Moirai\DDL\Constraints\ColumnConstraints;
use Moirai\DDL\Constraints\TableConstraints;
use Moirai\DDL\Shared\DataTypes;
use Moirai\DDL\Shared\Indexes;

class OracleLexis extends Lexis implements LexisInterface
{
    /**
     * @var array|string[]
     */
    protected array $dataTypes = [
        DataTypes::NUMBER => 'NUMBER({precision_and_scale})',
        DataTypes::FLOAT => 'FLOAT({precision})',
        DataTypes::BINARY_FLOAT => 'BINARY_FLOAT',
        DataTypes::BINARY_DOUBLE => 'BINARY_DOUBLE',
        DataTypes::CHAR => 'CHAR({length})',
        DataTypes::VARCHAR_2 => 'VARCHAR2({length})',
        DataTypes::N_CHAR => 'NCHAR({length})',
        DataTypes::N_VARCHAR_2 => 'NVARCHAR2({length})',
        DataTypes::CLOB => 'CLOB',
        DataTypes::NCLOB => 'NCLOB',
        DataTypes::BLOB => 'BLOB',
        DataTypes::RAW => 'RAW({length})',
        DataTypes::LONG => 'LONG',
        DataTypes::DATE => 'DATE',
        DataTypes::TIMESTAMP => 'TIMESTAMP({precision})',
        DataTypes::TIMESTAMP_TZ => 'TIMESTAMP({precision}) WITH TIME ZONE',
        DataTypes::TIMESTAMP_LTZ => 'TIMESTAMP WITH LOCAL TIME ZONE({precision})',
        DataTypes::INTERVAL_YEAR_TO_MONTH => 'INTERVAL YEAR TO MONTH',
        DataTypes::INTERVAL_DAY_TO_SECOND => 'INTERVAL DAY({day_precision}) TO SECOND({second_precision})',
        DataTypes::UROWID => 'UROWID',
        DataTypes::XML => 'XMLType'
    ];

    /**
     * @var array|string[]
     */
    protected array $columnConstraints = [
        ColumnConstraints::CHECK => 'CHECK({column} >= 0)',
        ColumnConstraints::NOT_NULL => 'NOT NULL',
        ColumnConstraints::UNIQUE => 'UNIQUE',
        ColumnConstraints::DEFAULT => 'DEFAULT "{value}"',
        ColumnConstraints::PRIMARY_KEY => 'PRIMARY KEY',
        ColumnConstraints::COMMENT => 'COMMENT ON COLUMN {table}.{column} IS \'{value}\''
    ];

    /**
     * @var array|string[]
     */
    protected array $tableConstraints = [
        TableConstraints::CHECK => 'CONSTRAINT {name} CHECK({expression})',
        TableConstraints::UNIQUE => 'CONSTRAINT {name} UNIQUE({columns})',
        TableConstraints::PRIMARY_KEY => 'CONSTRAINT {name} PRIMARY KEY ({columns})',
        TableConstraints::FOREIGN_KEY => 'CONSTRAINT {name} FOREIGN KEY ({columns}) REFERENCES {referenced_table}({referenced_columns}) ON DELETE {on_delete_action}',
    ];

    /**
     * @var array|string[]
     */
    protected array $indexes = [
        Indexes::INDEX => 'CREATE INDEX {name} ON {table} ({columns})',
        Indexes::UNIQUE => 'CREATE UNIQUE INDEX {name} ON {table} ({columns})',
        Indexes::BITMAP => 'CREATE BITMAP INDEX {name} ON {table} ({columns})',
        Indexes::REVERSE => 'CREATE INDEX {name} ON {table} ({columns}) REVERSE',
        Indexes::SPATIAL => 'CREATE INDEX {name} ON {table} ({columns}) INDEXTYPE IS MDSYS.SPATIAL_INDEX',
        Indexes::PARTIAL => 'CREATE INDEX {name} ON {table} ({columns}) PARTITION BY RANGE ({expression})',
        Indexes::GLOBAL => 'CREATE INDEX {name} ON {table} ({columns}) GLOBAL',
        Indexes::LOCAL => 'CREATE INDEX {name} ON {table} ({columns}) LOCAL',
        Indexes::COMPRESS => 'CREATE INDEX {name} ON {table} ({columns}) COMPRESS',
        Indexes::CLUSTERED => 'CREATE CLUSTER {name} ({columns})'
    ];

    /**
     * @var array|string[]
     */
    protected array $alterActions = [
        AlterActions::ADD_COLUMN => 'ADD ({definition})',
        AlterActions::MODIFY_COLUMN => 'MODIFY ({column} {definition})',
        AlterActions::RENAME_COLUMN => 'RENAME COLUMN {old_name} TO {new_name}',
        AlterActions::DROP_COLUMN => 'DROP COLUMN {name}',
        AlterActions::SET_DEFAULT => 'MODIFY ({column} DEFAULT {value})',
        AlterActions::DROP_DEFAULT => 'MODIFY ({name} DROP DEFAULT)',
        AlterActions::ADD_CHECK_CONSTRAINT => 'ADD CONSTRAINT {name} CHECK ({expression})',
        AlterActions::DROP_CHECK_CONSTRAINT => 'DROP CONSTRAINT {name}',
        AlterActions::ADD_UNIQUE_CONSTRAINT => 'ADD CONSTRAINT {name} UNIQUE ({columns})',
        AlterActions::ADD_PRIMARY_KEY_CONSTRAINT => 'ADD CONSTRAINT {name} PRIMARY KEY ({columns})',
        AlterActions::DROP_PRIMARY_KEY_CONSTRAINT => 'DROP CONSTRAINT {name}',
        AlterActions::ADD_FOREIGN_KEY_CONSTRAINT => 'ADD CONSTRAINT {name} FOREIGN KEY ({columns}) REFERENCES {referenced_table} ({referenced_column}) ON DELETE {on_delete_action} ON UPDATE {on_update_action}',
        AlterActions::DROP_FOREIGN_KEY_CONSTRAINT => 'DROP CONSTRAINT {name}',
        AlterActions::DROP_INDEX => 'DROP INDEX {name}',
        AlterActions::ENABLE_KEYS => 'ENABLE ALL CONSTRAINTS',
        AlterActions::DISABLE_KEYS => 'DISABLE ALL CONSTRAINTS',
        AlterActions::LOCK_TABLE => 'LOCK TABLE {table} IN EXCLUSIVE MODE',
        AlterActions::UNLOCK_TABLE => 'UNLOCK TABLE {table}',
        AlterActions::RENAME_TABLE => 'RENAME TO {new_name}',
        AlterActions::CHANGE_TABLESPACE => 'MOVE TABLESPACE {tablespace_name}',
        AlterActions::SET_STORAGE => 'MODIFY ({column} STORAGE {storage_type})',
        AlterActions::CREATE_SEQUENCE => 'CREATE SEQUENCE {name}',
        AlterActions::DROP_SEQUENCE => 'DROP SEQUENCE {name}',
        AlterActions::RENAME_SEQUENCE => 'ALTER SEQUENCE {old_name} RENAME TO {new_name}',
    ];
}
