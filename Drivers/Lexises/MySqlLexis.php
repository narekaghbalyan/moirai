<?php

namespace Moirai\Drivers\Lexises;

use Moirai\DDL\AlterActions;
use Moirai\DDL\Constraints\ColumnConstraints;
use Moirai\DDL\Constraints\TableConstraints;
use Moirai\DDL\DataTypes;
use Moirai\DDL\Indexes;

class MySqlLexis extends Lexis implements LexisInterface
{
    /**
     * @var array|string[]
     */
    protected array $dataTypes = [
        DataTypes::TINY_INTEGER => 'TINYINT',
        DataTypes::SMALL_INTEGER => 'SMALLINT',
        DataTypes::MEDIUM_INTEGER => 'MEDIUMINT',
        DataTypes::INTEGER => 'INT',
        DataTypes::BIG_INTEGER => 'BIGINT',
        DataTypes::FLOAT => 'FLOAT({precision})',
        DataTypes::DOUBLE => 'DOUBLE({precision})',
        DataTypes::DECIMAL => 'DECIMAL({precision_and_scale})',
        DataTypes::NUMERIC => 'NUMERIC({precision_and_scale})',
        DataTypes::BIT => 'BIT({size})',
        DataTypes::CHAR => 'CHAR({length})',
        DataTypes::VARCHAR => 'VARCHAR({length})',
        DataTypes::TINY_TEXT => 'TINY TEXT',
        DataTypes::TEXT => 'TEXT',
        DataTypes::MEDIUM_TEXT => 'MEDIUM TEXT',
        DataTypes::LONG_TEXT => 'LONG TEXT',
        DataTypes::BINARY => 'BINARY({length})',
        DataTypes::VARBINARY => 'VARBINARY({length})',
        DataTypes::TINY_BLOB => 'TINY BLOB',
        DataTypes::BLOB => 'BLOB',
        DataTypes::MEDIUM_BLOB => 'MEDIUM BLOB',
        DataTypes::LONG_BLOB => 'LONG BLOB',
        DataTypes::DATE => 'DATE',
        DataTypes::DATE_TIME => 'DATETIME',
        DataTypes::TIMESTAMP => 'TIMESTAMP({precision})',
        DataTypes::TIME => 'TIME({precision})',
        DataTypes::YEAR => 'YEAR',
        DataTypes::ENUM => 'ENUM({white_list})',
        DataTypes::SET => 'SET({white_list})',
        DataTypes::JSON => 'JSON',
        DataTypes::POINT => 'POINT',
        DataTypes::LINE_STRING => 'LINESTRING',
        DataTypes::POLYGON => 'POLYGON',
        DataTypes::GEOMETRY => 'GEOMETRY',
        DataTypes::GEOMETRY_COLLECTION => 'GEOMETRYCOLLECTION',
        DataTypes::MULTI_POINT => 'MULTIPOINT',
        DataTypes::MULTI_LINE_STRING => 'MULTILINESTRING',
        DataTypes::MULTI_POLYGON => 'MULTIPOLYGON'
    ];

    /**
     * @var array|string[]
     */
    protected array $columnConstraints = [
        ColumnConstraints::UNSIGNED => 'UNSIGNED',
        ColumnConstraints::CHECK => 'CHECK({column} >= 0)',
        ColumnConstraints::AUTOINCREMENT => 'AUTO_INCREMENT',
        ColumnConstraints::NOT_NULL => 'NOT NULL',
        ColumnConstraints::UNIQUE => 'UNIQUE',
        ColumnConstraints::DEFAULT => 'DEFAULT "{value}"',
        ColumnConstraints::COLLATION => 'COLLATE {value}',
        ColumnConstraints::CHARSET => 'CHARACTER SET {value}',
        ColumnConstraints::PRIMARY_KEY => 'PRIMARY KEY',
        ColumnConstraints::INVISIBLE => 'INVISIBLE',
        ColumnConstraints::COMMENT => 'COMMENT "{value}"'
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
        Indexes::UNIQUE => 'CREATE UNIQUE INDEX {name} ON {table} ({columns})',
        Indexes::PRIMARY_KEY => 'ALTER TABLE {table} ADD PRIMARY KEY ({columns})',
        Indexes::FULL_TEXT => 'CREATE FULLTEXT INDEX {name} ON {table} ({columns})',
        Indexes::SPATIAL => 'CREATE SPATIAL INDEX {name} ON {table} ({columns})',
        Indexes::HASH => 'CREATE INDEX {name} ON {table} ({columns}) USING HASH',
        Indexes::INVISIBLE => 'CREATE INDEX {name} ON {table} ({columns}) INVISIBLE'
    ];

    /**
     * @var array|string[]
     */
    protected array $alterActions = [
        AlterActions::ADD_COLUMN => '{table} ADD COLUMN {column} {definition}',
        AlterActions::ADD_COMPUTED_COLUMN => '{table} ADD COLUMN {column} {expression} GENERATED ALWAYS AS ({expression}) STORED',
        AlterActions::MODIFY_COLUMN => '{table} MODIFY COLUMN {column} {definition}',
        AlterActions::RENAME_COLUMN => '{table} CHANGE COLUMN {old_name} {new_name} {definition}',
        AlterActions::DROP_COLUMN => '{table} DROP COLUMN {column}',
        AlterActions::DROP_DEFAULT => '{table} ALTER COLUMN {column} DROP DEFAULT',

        AlterActions::ADD_CHECK_CONSTRAINT => '{table} ADD CONSTRAINT {name} CHECK ({expression})',
        AlterActions::DROP_CHECK_CONSTRAINT => '{table} DROP CONSTRAINT {name}',
        AlterActions::ADD_UNIQUE_CONSTRAINT => '{table} ADD CONSTRAINT {name} UNIQUE ({columns})',
        AlterActions::ADD_PRIMARY_KEY_CONSTRAINT => '{table} ADD PRIMARY KEY ({columns})',
        AlterActions::DROP_PRIMARY_KEY_CONSTRAINT => '{table} DROP PRIMARY KEY',
        AlterActions::ADD_FOREIGN_KEY_CONSTRAINT => '{table} ADD CONSTRAINT {name} FOREIGN KEY ({columns}) REFERENCES {referenced_table}({referenced_columns}) ON DELETE {on_delete_action} ON UPDATE {on_update_action}',
        AlterActions::DROP_FOREIGN_KEY_CONSTRAINT => '{table} DROP FOREIGN KEY {name}',
        AlterActions::DROP_INDEX => '{table} DROP INDEX {name}',

        AlterActions::ENABLE_KEYS => '{table} ENABLE KEYS',
        AlterActions::DISABLE_KEYS => '{table} DISABLE KEYS',
        AlterActions::ADD_PARTITION => '{table} PARTITION BY RANGE ({column}) ({expression})',
        AlterActions::DROP_PARTITION => '{table} DROP PARTITION {name}',
        AlterActions::LOCK_TABLE => 'LOCK TABLES {table} WRITE',
        AlterActions::UNLOCK_TABLE => 'UNLOCK TABLES',
        AlterActions::RENAME_TABLE => '{table} RENAME TO {new_name}',
        AlterActions::CHANGE_ENGINE => '{table} ENGINE = {engine}',
        AlterActions::CHANGE_ROW_FORMAT => '{table} ROW_FORMAT = {format}',
        AlterActions::CHANGE_AUTO_INCREMENT => '{table} AUTO_INCREMENT = {value}',
        AlterActions::CHANGE_TABLESPACE => '{table} TABLESPACE {name}',
    ];
}