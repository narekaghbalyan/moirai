<?php

namespace Moirai\Drivers\Lexises;

use Moirai\DDL\AlterActions;
use Moirai\DDL\Constraints\ColumnConstraints;
use Moirai\DDL\Constraints\TableConstraints;
use Moirai\DDL\DataTypes;
use Moirai\DDL\Indexes;

class MsSqlServerLexis extends Lexis implements LexisInterface
{
    /**
     * @var array|string[]
     */
    protected array $dataTypes = [
        DataTypes::INTEGER => 'INT',
        DataTypes::SMALL_INTEGER => 'SMALLINT',
        DataTypes::TINY_INTEGER => 'TINYINT',
        DataTypes::BIG_INTEGER => 'BIGINT',
        DataTypes::DECIMAL => 'DECIMAL({precision_and_scale})',
        DataTypes::NUMERIC => 'NUMERIC({precision_and_scale})',
        DataTypes::MONEY => 'MONEY',
        DataTypes::SMALL_MONEY => 'SMALLMONEY',
        DataTypes::FLOAT => 'FLOAT({precision})',
        DataTypes::REAL => 'REAL',
        DataTypes::CHAR => 'CHAR({length})',
        DataTypes::VARCHAR => 'VARCHAR({length})',
        DataTypes::TEXT => 'TEXT',
        DataTypes::N_CHAR => 'NCHAR({length})',
        DataTypes::N_VARCHAR => 'NVARCHAR({length})',
        DataTypes::N_TEXT => 'NTEXT',
        DataTypes::BINARY => 'BINARY({length})',
        DataTypes::VARBINARY => 'VARBINARY({length})',
        DataTypes::IMAGE => 'IMAGE',
        DataTypes::DATE => 'DATE',
        DataTypes::TIME => 'TIME({precision})',
        DataTypes::DATE_TIME => 'DATETIME',
        DataTypes::DATE_TIME_2 => 'DATETIME2({precision})',
        DataTypes::SMALL_DATE_TIME => 'SMALLDATETIME',
        DataTypes::DATE_TIME_OFFSET => 'DATETIMEOFFSET({precision})',
        DataTypes::BIT => 'BIT',
        DataTypes::UUID => 'UNIQUEIDENTIFIER',
        DataTypes::XML => 'XML',
        DataTypes::JSON => 'NVARCHAR({max})',
        DataTypes::SQL_VARIANT => 'SQL_VARIANT',
        DataTypes::ROW_VERSION => 'ROWVERSION',
        DataTypes::GEOMETRY => 'GEOMETRY',
        DataTypes::GEOGRAPHY => 'GEOGRAPHY',
        DataTypes::HIERARYCHYID => 'HIERARCHYID'
    ];

    /**
     * @var array|string[]
     */
    protected array $columnConstraints = [
        ColumnConstraints::CHECK => 'CHECK({column} >= 0)',
        ColumnConstraints::AUTOINCREMENT => 'IDENTITY',
        ColumnConstraints::NOT_NULL => 'NOT NULL',
        ColumnConstraints::UNIQUE => 'UNIQUE',
        ColumnConstraints::DEFAULT => 'DEFAULT "{value}"',
        ColumnConstraints::COLLATION => 'COLLATE {value}',
        ColumnConstraints::PRIMARY_KEY => 'PRIMARY KEY',
    ];

    /**
     * @var array|string[]
     */
    protected array $tableConstraints = [
        TableConstraints::CHECK => 'CONSTRAINT {name} CHECK({expression})',
        TableConstraints::UNIQUE => 'CONSTRAINT {name} UNIQUE({columns})',
        TableConstraints::PRIMARY_KEY => 'CONSTRAINT {name} PRIMARY KEY ({columns})',
        TableConstraints::FOREIGN_KEY => 'CONSTRAINT {name} FOREIGN KEY ({columns}) REFERENCES {referenced_table}({referenced_columns}) ON DELETE {on_delete_action} ON UPDATE {on_update_action}',
    ];

    /**
     * @var array|string[]
     */
    protected array $indexes = [
        Indexes::INDEX => 'CREATE INDEX {name} ON {table} ({columns})',
        Indexes::CLUSTERED => 'CREATE CLUSTERED INDEX {name} ON {table} ({columns})',
        Indexes::NON_CLUSTERED => 'CREATE NONCLUSTERED INDEX {name} ON {table} ({columns})',
        Indexes::UNIQUE => 'CREATE UNIQUE INDEX {name} ON {table} ({columns})',
        Indexes::FULL_TEXT => 'CREATE FULLTEXT INDEX ON {table} ({columns}) KEY INDEX {name}',
        Indexes::XML => 'CREATE PRIMARY XML INDEX {name} ON {table} ({columns})',
        Indexes::SPATIAL => 'CREATE SPATIAL INDEX {name} ON {table} ({columns})',
        Indexes::PARTIAL => 'CREATE NONCLUSTERED INDEX {name} ON {table} ({columns}) {expression}',
        Indexes::COLUMNSTORE => 'CREATE CLUSTERED COLUMNSTORE INDEX {name} ON {table} ({columns})',
        Indexes::INCLUDE => 'CREATE NONCLUSTERED INDEX {name} ON {table} ({columns}) INCLUDE ({included_columns})'
    ];




    /**
     * @var array|string[]
     */
    protected array $alterActions = [
        AlterActions::ADD_COLUMN => '{table} ADD {column} {definition}',
        AlterActions::ADD_COMPUTED_COLUMN => '{table} ADD {column} AS {expression} PERSISTED',
        AlterActions::MODIFY_COLUMN => '{table} ALTER COLUMN {column} {definition}',
        AlterActions::RENAME_COLUMN => 'EXEC sp_rename \'{table}.{old_name}\', \'{new_name}\', \'COLUMN\'',
        AlterActions::DROP_COLUMN => '{table} DROP COLUMN {column}',

        AlterActions::SET_DEFAULT => '{table} ADD CONSTRAINT {name} DEFAULT {value} FOR {column}',
        AlterActions::DROP_DEFAULT => '{table} DROP CONSTRAINT {name}',

        AlterActions::ADD_CHECK_CONSTRAINT => '{table} ADD CONSTRAINT {name} CHECK ({expression})',
        AlterActions::DROP_CHECK_CONSTRAINT => '{table} DROP CONSTRAINT {name}',
        AlterActions::ADD_UNIQUE_CONSTRAINT => '{table} ADD CONSTRAINT {name} UNIQUE ({columns})',
        AlterActions::ADD_PRIMARY_KEY_CONSTRAINT => '{table} ADD CONSTRAINT {name} PRIMARY KEY ({columns})',
        AlterActions::DROP_PRIMARY_KEY_CONSTRAINT => '{table} DROP CONSTRAINT {name}',
        AlterActions::ADD_FOREIGN_KEY_CONSTRAINT => '{table} ADD CONSTRAINT {name} FOREIGN KEY ({columns}) REFERENCES {referenced_table} ({referenced_column}) ON DELETE {on_delete_action} ON UPDATE {on_update_action}',
        AlterActions::DROP_FOREIGN_KEY_CONSTRAINT => '{table} DROP CONSTRAINT {name}',
        AlterActions::DROP_INDEX => 'DROP INDEX {name} ON {table}', // without altering table

        AlterActions::ADD_PARTITION => 'ALTER PARTITION SCHEME {scheme_name} NEXT USED {partition_function}',
        AlterActions::DROP_PARTITION => 'ALTER PARTITION FUNCTION {partition_function_name} SPLIT RANGE ({value})',

        AlterActions::LOCK_TABLE => '{table} WITH (TABLOCK)',
        AlterActions::UNLOCK_TABLE => '{table} WITH (NOLOCK)',

        AlterActions::RENAME_TABLE => 'EXEC sp_rename \'{old_name}\', \'{new_name}\'',

        AlterActions::CHANGE_TABLESPACE => 'ALTER SCHEMA {new_schema} TRANSFER {table}',

        AlterActions::CREATE_SEQUENCE => 'CREATE SEQUENCE {sequence_name} START WITH {start_value} INCREMENT BY {increment_value}',
        AlterActions::DROP_SEQUENCE => 'DROP SEQUENCE {sequence_name}',
        AlterActions::RENAME_SEQUENCE => 'EXEC sp_rename \'{old_name}\', \'{new_name}\'',

        AlterActions::ENABLE_KEYS => '{table} ENABLE TRIGGER ALL',
        AlterActions::DISABLE_KEYS => '{table} DISABLE TRIGGER ALL',
    ];




}