<?php

namespace Moirai\Drivers\Lexises;

use Moirai\DDL\Shared\AlterActions;
use Moirai\DDL\Constraints\ColumnConstraints;
use Moirai\DDL\Constraints\TableConstraints;
use Moirai\DDL\Shared\DataTypes;
use Moirai\DDL\Shared\Indexes;

class SqliteLexis extends Lexis implements LexisInterface
{
    /**
     * @var array|string[]
     */
    protected array $dataTypes = [
        DataTypes::INTEGER => 'INTEGER',
        DataTypes::REAL => 'REAL',
        DataTypes::TEXT => 'TEXT',
        DataTypes::BLOB => 'BLOB',
        DataTypes::NUMERIC => 'NUMERIC',
        DataTypes::CHAR => 'CHAR({length})',
        DataTypes::VARCHAR => 'VARCHAR({length})',
        DataTypes::DECIMAL => 'DECIMAL',
        DataTypes::DATE => 'DATE',
        DataTypes::DATE_TIME => 'DATETIME',
    ];

    /**
     * @var array|string[]
     */
    protected array $columnConstraints = [
        ColumnConstraints::CHECK => 'CHECK({column} >= 0)',
        ColumnConstraints::AUTOINCREMENT => 'AUTOINCREMENT',
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
        TableConstraints::FOREIGN_KEY => 'CONSTRAINT {name} FOREIGN KEY ({columns}) REFERENCES {referenced_table}({referenced_columns}) ON DELETE {on_delete_action} ON UPDATE {on_update_action}'
    ];

    /**
     * @var array|string[]
     */
    protected array $indexes = [
        Indexes::INDEX => 'CREATE INDEX {name} ON {table} ({columns})',
        Indexes::UNIQUE => 'CREATE UNIQUE INDEX {name} ON {table} ({columns})',
        Indexes::PARTIAL => 'CREATE UNIQUE INDEX {name} ON {table} ({columns}) {expression}'
    ];

    /**
     * @var array|string[]
     */
    protected array $alterActions = [
        AlterActions::ADD_COLUMN => 'ADD COLUMN {definition}',
        AlterActions::RENAME_COLUMN => 'RENAME COLUMN {old_name} TO {new_name};',
        AlterActions::DROP_INDEX => 'DROP INDEX {name}',
        AlterActions::RENAME_TABLE => 'RENAME TO {new_name}',
    ];
}
