<?php

namespace Moirai\Drivers\Lexises;

use Moirai\DDL\AlterActions;
use Moirai\DDL\Constraints\ColumnConstraints;
use Moirai\DDL\Constraints\TableConstraints;
use Moirai\DDL\DataTypes;
use Moirai\DDL\Indexes;

class PostgreSqlLexis extends Lexis implements LexisInterface
{
    /**
     * @var array|string[]
     */
    protected array $dataTypes = [
        DataTypes::SMALL_INTEGER => 'SMALLINT',
        DataTypes::INTEGER => 'INTEGER',
        DataTypes::BIG_INTEGER => 'BIGINT',
        DataTypes::DECIMAL => 'DECIMAL({precision_and_scale})',
        DataTypes::NUMERIC => 'NUMERIC({precision_and_scale})',
        DataTypes::FLOAT => 'FLOAT',
        DataTypes::REAL => 'REAL',
        DataTypes::DOUBLE => 'DOUBLE PRECISION',
        DataTypes::MONEY => 'MONEY',
        DataTypes::CHAR => 'CHAR({length})',
        DataTypes::VARCHAR => 'VARCHAR({length})',
        DataTypes::TEXT => 'TEXT',
        DataTypes::BYTEA => 'BYTEA',
        DataTypes::DATE => 'DATE',
        DataTypes::TIME => 'TIME({precision})',
        DataTypes::TIMESTAMP => 'TIMESTAMP({precision})',
        DataTypes::TIME_TZ => 'TIME({precision}) WITH TIME ZONE',
        DataTypes::TIMESTAMP_TZ => 'TIMESTAMP({precision}) WITH TIME ZONE',
        DataTypes::BOOLEAN => 'BOOLEAN',
        DataTypes::UUID => 'UUID',
        DataTypes::JSON => 'JSON',
        DataTypes::JSONB => 'JSONB',
        DataTypes::XML => 'XML',
        DataTypes::HSTORE => 'HSTORE',
        DataTypes::INET => 'INET',
        DataTypes::CIDR => 'CIDR',
        DataTypes::POINT => 'POINT',
        DataTypes::LINE => 'LINE',
        DataTypes::LSEG => 'LSEG',
        DataTypes::BOX => 'BOX',
        DataTypes::POLYGON => 'POLYGON',
        DataTypes::CIRCLE => 'CIRCLE'
    ];

    /**
     * @var array|string[]
     */
    protected array $columnConstraints = [
        ColumnConstraints::CHECK => 'CHECK({column} >= 0)',
        ColumnConstraints::AUTOINCREMENT => 'SERIAL',
        ColumnConstraints::NOT_NULL => 'NOT NULL',
        ColumnConstraints::UNIQUE => 'UNIQUE',
        ColumnConstraints::DEFAULT => 'DEFAULT "{value}"',
        ColumnConstraints::COLLATION => 'COLLATE {value}',
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
        TableConstraints::FOREIGN_KEY => 'CONSTRAINT {name} FOREIGN KEY ({columns}) REFERENCES {referenced_table}({referenced_columns}) ON DELETE {on_delete_action} ON UPDATE {on_update_action}',
    ];

    /**
     * @var array|string[]
     */
    protected array $indexes = [
        Indexes::INDEX => 'CREATE INDEX {name} ON {table} ({columns})',
        Indexes::HASH => 'CREATE INDEX {name} ON {table} USING hash ({columns})',
        Indexes::GIN => 'CREATE INDEX {name} ON {table} USING gin ({columns})',
        Indexes::GIST => 'CREATE INDEX {name} ON {table} USING gist ({columns})',
        Indexes::SPGIST => 'CREATE INDEX {name} ON {table} USING spgist ({columns})',
        Indexes::BRIN => 'CREATE INDEX {name} ON {table} USING brin ({columns})',
        Indexes::BLOOM => 'CREATE INDEX {name} ON {table} USING bloom ({columns})',
        Indexes::PARTIAL => 'CREATE INDEX {name} ON {table} ({columns}) {expression}',
        Indexes::UNIQUE => 'CREATE UNIQUE INDEX {name} ON {table} ({columns})'
    ];

    /**
     * @var array|string[]
     */
    protected array $alterActions = [
        AlterActions::ADD_COLUMN => 'ADD COLUMN {definition}',
        AlterActions::ADD_COMPUTED_COLUMN => 'ADD COLUMN {name} {definition} GENERATED ALWAYS AS ({expression}) STORED',
        AlterActions::DROP_COMPUTED_COLUMN => 'DROP COLUMN {name}',
        AlterActions::MODIFY_COLUMN => 'ALTER COLUMN {column} SET DATA TYPE {definition}',
        AlterActions::RENAME_COLUMN => 'RENAME COLUMN {old_name} TO {new_name}',
        AlterActions::DROP_COLUMN => 'DROP COLUMN {name}',
        AlterActions::SET_DEFAULT => 'ALTER COLUMN {column} SET DEFAULT {value}',
        AlterActions::DROP_DEFAULT => 'ALTER COLUMN {name} DROP DEFAULT',
        AlterActions::ADD_CHECK_CONSTRAINT => 'ADD CONSTRAINT {name} CHECK ({expression})',
        AlterActions::DROP_CHECK_CONSTRAINT => 'DROP CONSTRAINT {name}',
        AlterActions::ADD_UNIQUE_CONSTRAINT => 'ADD CONSTRAINT {name} UNIQUE ({columns})',
        AlterActions::ADD_PRIMARY_KEY_CONSTRAINT => 'ADD CONSTRAINT {name} PRIMARY KEY ({columns})',
        AlterActions::DROP_PRIMARY_KEY_CONSTRAINT => 'DROP CONSTRAINT {name}',
        AlterActions::ADD_FOREIGN_KEY_CONSTRAINT => 'ADD CONSTRAINT {name} FOREIGN KEY ({columns}) REFERENCES {referenced_table} ({referenced_column}) ON DELETE {on_delete_action} ON UPDATE {on_update_action}',
        AlterActions::DROP_FOREIGN_KEY_CONSTRAINT => 'DROP CONSTRAINT {name}',
        AlterActions::DROP_INDEX => 'DROP INDEX {name}',
        AlterActions::ENABLE_KEYS => 'ENABLE TRIGGER ALL',
        AlterActions::DISABLE_KEYS => 'DISABLE TRIGGER ALL',
        AlterActions::LOCK_TABLE => 'LOCK TABLE {table} IN ACCESS EXCLUSIVE MODE',
        AlterActions::UNLOCK_TABLE => 'UNLOCK TABLE {table}',
        AlterActions::RENAME_TABLE => 'RENAME TO {new_name}',
        AlterActions::CHANGE_TABLESPACE => 'SET TABLESPACE {tablespace_name}',
        AlterActions::SET_STORAGE => 'ALTER COLUMN {column} SET STORAGE {storage_type}',
        AlterActions::ADD_EXTENSION => 'CREATE EXTENSION IF NOT EXISTS {name}',
        AlterActions::DROP_EXTENSION => 'DROP EXTENSION {name}',
        AlterActions::CREATE_SEQUENCE => 'CREATE SEQUENCE {name}',
        AlterActions::DROP_SEQUENCE => 'DROP SEQUENCE {name}',
        AlterActions::RENAME_SEQUENCE => 'ALTER SEQUENCE {old_name} RENAME TO {new_name}',
    ];

}