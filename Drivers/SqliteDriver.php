<?php

namespace Moirai\Drivers;

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
        'NULL' => 'NULL',                      // NULL value
        'INTEGER' => 'INTEGER',                // Signed integer
        'REAL' => 'REAL',                      // Floating point
        'TEXT' => 'TEXT',                      // Text string
        'BLOB' => 'BLOB',                      // Binary large object
        'BOOLEAN' => 'BOOLEAN',                // Boolean value (stored as INTEGER 0 or 1)
        'NUMERIC' => 'NUMERIC',                // Numeric value
        'CHAR' => 'CHAR(n)',                   // Fixed-length character string
        'VARCHAR' => 'VARCHAR(n)',              // Variable-length character string
        'DECIMAL' => 'DECIMAL(p, s)',          // Exact numeric with precision
        'DATE' => 'DATE',                      // Date value
        'DATETIME' => 'DATETIME',              // Date and time value
    ];


    /**
     * SqliteDriver constructor.
     */
    public function __construct()
    {
        $this->initializeDriverGrammaticalStructure();
    }
}