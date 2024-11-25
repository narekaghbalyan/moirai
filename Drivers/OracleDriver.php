<?php

namespace Moirai\Drivers;

use Moirai\DDL\ColumnConstraints;
use Moirai\DDL\DataTypes;

class OracleDriver extends Driver
{
    /**
     * @var array
     */
    protected array $pitaForColumns = [
        'opening' => '"',
        'closing' => '"'
    ];

    /**
     * @var array
     */
    protected array $pitaForStrings = [
        'opening' => '\'',
        'closing' => '\''
    ];

    /**
     * @var array|string[]
     */
    protected array $dataTypes = [
        DataTypes::NUMBER => 'NUMBER{precision_and_scale}',
        DataTypes::FLOAT => 'FLOAT{precision}',
        DataTypes::BINARY_FLOAT => 'BINARY_FLOAT',
        DataTypes::BINARY_DOUBLE => 'BINARY_DOUBLE',
        DataTypes::CHAR => 'CHAR{length}',
        DataTypes::VARCHAR_2 => 'VARCHAR2{length}',
        DataTypes::N_CHAR => 'NCHAR{length}',
        DataTypes::N_VARCHAR_2 => 'NVARCHAR2{length}',
        DataTypes::CLOB => 'CLOB',
        DataTypes::NCLOB => 'NCLOB',
        DataTypes::BLOB => 'BLOB',
        DataTypes::RAW => 'RAW(length)',
        DataTypes::LONG => 'LONG',


        DataTypes::DATE => 'DATE',
        DataTypes::TIMESTAMP => 'TIMESTAMP{precision}',
        DataTypes::TIMESTAMP_TZ => 'TIMESTAMP{precision} WITH TIME ZONE',
        DataTypes::TIMESTAMP_LTZ => 'TIMESTAMP WITH LOCAL TIME ZONE{precision}',
        DataTypes::INTERVAL_YEAR_TO_MONTH => 'INTERVAL YEAR TO MONTH',
        DataTypes::INTERVAL_DAY_TO_SECOND => 'INTERVAL DAY{day_precision} TO SECOND{second_precision}',



        DataTypes::UROWID => 'UROWID',

        DataTypes::XML => 'XMLType'
    ];

    private array $constraints = [
        ColumnConstraints::CHECK => 'CHECK({column} >= 0)',
        ColumnConstraints::NOT_NULL => 'NOT NULL',
        ColumnConstraints::UNIQUE => 'UNIQUE',
        ColumnConstraints::DEFAULT => 'DEFAULT "{value}"',
        ColumnConstraints::PRIMARY_KEY => 'PRIMARY KEY',
        ColumnConstraints::FOREIGN_KEY => 'FOREIGN KEY ({column}) REFERENCES {table}({column})',
        ColumnConstraints::ON_DELETE => 'ON DELETE {action}',
        ColumnConstraints::INDEX => 'INDEX {name} ({column})',
        ColumnConstraints::COMMENT => 'COMMENT ON COLUMN {table}.{column} IS \'{value}\''
    ];


    /**
     * OracleDriver constructor.
     */
    public function __construct()
    {
        $this->initializeDriverGrammaticalStructure();
    }
}
