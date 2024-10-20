<?php

namespace Moirai\Drivers;

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
        DataTypes::NUMBER => 'NUMBER(p, s)',  // Precision and scale can be specified
        DataTypes::FLOAT => 'FLOAT',           // Synonym for NUMBER
        DataTypes::BINARY_FLOAT => 'BINARY_FLOAT',
        DataTypes::BINARY_DOUBLE => 'BINARY_DOUBLE',
        DataTypes::CHAR => 'CHAR(n)',         // Fixed-length character data
        DataTypes::VARCHAR_2 => 'VARCHAR2(n)', // Variable-length character data
        DataTypes::N_CHAR => 'NCHAR(n)',       // Fixed-length Unicode character data
        DataTypes::N_VARCHAR_2 => 'NVARCHAR2(n)', // Variable-length Unicode character data
        DataTypes::CLOB => 'CLOB',             // Character Large Object
        DataTypes::N_CLOB => 'NCLOB',           // National Character Large Object
        DataTypes::BLOB => 'BLOB',             // Binary Large Object
        DataTypes::RAW => 'RAW(n)',            // Fixed-length binary data
        DataTypes::LONG => 'LONG',             // Variable-length character data (deprecated)
        DataTypes::DATE => 'DATE',             // Date and time
        DataTypes::TIMESTAMP => 'TIMESTAMP',   // Date and time with fractional seconds
        DataTypes::TIMESTAMP_TZ => 'TIMESTAMP WITH TIME ZONE',
        DataTypes::TIMESTAMP_LTZ => 'TIMESTAMP WITH LOCAL TIME ZONE',
        DataTypes::INTERVAL_YEAR_TO_MONTH => 'INTERVAL YEAR TO MONTH',
        DataTypes::INTERVAL_DAY_TO_SECOND => 'INTERVAL DAY TO SECOND',
        DataTypes::UROWID => 'UROWID',         // Universal row identifier
    ];


    /**
     * OracleDriver constructor.
     */
    public function __construct()
    {
        $this->initializeDriverGrammaticalStructure();
    }
}
