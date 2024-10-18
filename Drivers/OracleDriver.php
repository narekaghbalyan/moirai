<?php

namespace Moirai\Drivers;

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
        'NUMBER' => 'NUMBER(p, s)',  // Precision and scale can be specified
        'FLOAT' => 'FLOAT',           // Synonym for NUMBER
        'BINARY_FLOAT' => 'BINARY_FLOAT',
        'BINARY_DOUBLE' => 'BINARY_DOUBLE',
        'CHAR' => 'CHAR(n)',         // Fixed-length character data
        'VARCHAR2' => 'VARCHAR2(n)', // Variable-length character data
        'NCHAR' => 'NCHAR(n)',       // Fixed-length Unicode character data
        'NVARCHAR2' => 'NVARCHAR2(n)', // Variable-length Unicode character data
        'CLOB' => 'CLOB',             // Character Large Object
        'NCLOB' => 'NCLOB',           // National Character Large Object
        'BLOB' => 'BLOB',             // Binary Large Object
        'RAW' => 'RAW(n)',            // Fixed-length binary data
        'LONG' => 'LONG',             // Variable-length character data (deprecated)
        'DATE' => 'DATE',             // Date and time
        'TIMESTAMP' => 'TIMESTAMP',   // Date and time with fractional seconds
        'TIMESTAMP WITH TIME ZONE' => 'TIMESTAMP WITH TIME ZONE',
        'TIMESTAMP WITH LOCAL TIME ZONE' => 'TIMESTAMP WITH LOCAL TIME ZONE',
        'INTERVAL YEAR TO MONTH' => 'INTERVAL YEAR TO MONTH',
        'INTERVAL DAY TO SECOND' => 'INTERVAL DAY TO SECOND',
        'UROWID' => 'UROWID',         // Universal row identifier
    ];


    /**
     * OracleDriver constructor.
     */
    public function __construct()
    {
        $this->initializeDriverGrammaticalStructure();
    }
}