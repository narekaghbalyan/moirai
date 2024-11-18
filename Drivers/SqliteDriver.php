<?php

namespace Moirai\Drivers;

use Moirai\DDL\Accessories;
use Moirai\DDL\DataTypes;

class SqliteDriver extends Driver
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
        DataTypes::INTEGER => 'INTEGER',                // Signed integer
        DataTypes::REAL => 'REAL',                      // Floating point
        DataTypes::TEXT => 'TEXT',                      // Text string
        DataTypes::BLOB => 'BLOB',                      // Binary large object
        DataTypes::NUMERIC => 'NUMERIC',                // Numeric value
        DataTypes::CHAR => 'CHAR{length}',                   // Fixed-length character string
        DataTypes::VARCHAR => 'VARCHAR{length}',              // Variable-length character string
        DataTypes::DECIMAL => 'DECIMAL',          // Exact numeric with precision
        DataTypes::DATE => 'DATE',                      // Date value
        DataTypes::DATE_TIME => 'DATETIME',              // Date and time value
    ];

    private array $accessories = [
        Accessories::UNSIGNED => 'CHECK({column} >= 0)',
        Accessories::AUTOINCREMENT => 'AUTOINCREMENT',
        Accessories::PRIMARY => 'PRIMARY KEY',
        Accessories::NULLABLE => 'NULL',
        Accessories::UNIQUE => 'UNIQUE',
        Accessories::DEFAULT => 'DEFAULT "{value}"',
        Accessories::COLLATION => 'COLLATE {value}',
        Accessories::INDEX => 'INDEX {name} ({column})'
    ];


    /**
     * SqliteDriver constructor.
     */
    public function __construct()
    {
        $this->initializeDriverGrammaticalStructure();
    }
}
