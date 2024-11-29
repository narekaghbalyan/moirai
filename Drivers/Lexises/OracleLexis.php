<?php

namespace Moirai\Drivers\Lexises;

use Moirai\DDL\Constraints\ColumnConstraints;
use Moirai\DDL\Constraints\TableConstraints;
use Moirai\DDL\DataTypes;
use Moirai\DDL\Indexes;

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

    protected array $indexes = [
        Indexes::INDEX => 'CREATE INDEX {name} ON {table} ({columns})',
        Indexes::UNIQUE => 'CREATE UNIQUE INDEX {name} ON {table} ({columns})',
        Indexes::BITMAP => 'CREATE BITMAP INDEX {name} ON {table} ({columns})',
        Indexes::REVERSE => 'CREATE INDEX {name} ON {table} ({columns}) REVERSE',
        Indexes::SPATIAL => 'CREATE INDEX {name} ON {table} ({columns}) INDEXTYPE IS MDSYS.SPATIAL_INDEX',
        Indexes::PARTIAL => 'CREATE INDEX {name} ON {table} ({columns}) PARTITION BY RANGE ({column})',
        Indexes::GLOBAL => 'CREATE INDEX {name} ON {table} ({columns}) GLOBAL',
        Indexes::LOCAL => 'CREATE INDEX {name} ON {table} ({columns}) LOCAL',
        Indexes::COMPRESS => 'CREATE INDEX {name} ON {table} ({columns}) COMPRESS',
        Indexes::CLUSTERED => 'CREATE CLUSTER {name} ({columns})'
    ];
}