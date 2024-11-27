<?php

namespace Moirai\Drivers\Lexises;

use Moirai\DDL\Constraints\ColumnConstraints;
use Moirai\DDL\Constraints\TableConstraints;
use Moirai\DDL\DataTypes;

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
        DataTypes::INTERVAL => 'INTERVAL',
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
        TableConstraints::INDEX => 'INDEX {name} ({columns})'
    ];
}