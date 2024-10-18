<?php

namespace Moirai\Drivers;

class PostgreSqlDriver extends Driver
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
        'SMALLINT' => 'SMALLINT',                // 2 bytes
        'INTEGER' => 'INTEGER',                  // 4 bytes
        'BIGINT' => 'BIGINT',                    // 8 bytes
        'DECIMAL' => 'DECIMAL(precision, scale)', // Exact numeric with selectable precision
        'NUMERIC' => 'NUMERIC(precision, scale)', // Exact numeric
        'REAL' => 'REAL',                        // 4 bytes floating point
        'DOUBLE PRECISION' => 'DOUBLE PRECISION', // 8 bytes floating point
        'MONEY' => 'MONEY',                      // Currency type
        'CHAR' => 'CHAR(n)',                     // Fixed-length character
        'VARCHAR' => 'VARCHAR(n)',                // Variable-length character
        'TEXT' => 'TEXT',                        // Variable-length character with no specific length
        'BYTEA' => 'BYTEA',                      // Binary data
        'DATE' => 'DATE',                        // Date type
        'TIME' => 'TIME',                        // Time without time zone
        'TIMETZ' => 'TIME WITH TIME ZONE',      // Time with time zone
        'TIMESTAMP' => 'TIMESTAMP',              // Timestamp without time zone
        'TIMESTAMPTZ' => 'TIMESTAMP WITH TIME ZONE', // Timestamp with time zone
        'INTERVAL' => 'INTERVAL',                // Time interval
        'BOOLEAN' => 'BOOLEAN',                   // Boolean type
        'UUID' => 'UUID',                        // Universally Unique Identifier
        'JSON' => 'JSON',                        // JSON data type
        'JSONB' => 'JSONB',                      // Binary JSON data type
        'XML' => 'XML',                          // XML data type
        'ARRAY' => 'ARRAY[type]',                // Array type (e.g., INTEGER[])
        'HSTORE' => 'HSTORE',                    // Key-value pairs
        'INET' => 'INET',                        // IP address
        'CIDR' => 'CIDR',                        // IP subnet
        'POINT' => 'POINT',                      // Geometric point
        'LINE' => 'LINE',                        // Geometric line
        'LSEG' => 'LSEG',                        // Line segment
        'BOX' => 'BOX',                          // Geometric box
        'POLYGON' => 'POLYGON',                  // Geometric polygon
        'CIRCLE' => 'CIRCLE',                    // Geometric circle
    ];


    /**
     * @var array|int[]
     */
    private array $normalizationBitmasks = [0, 1, 2, 4, 8, 16, 32];

    /**
     * @var array|string[]
     */
    private array $weights = ['A', 'B', 'C', 'D'];

    /**
     * @var array|string[]
     */
    private array $highlightingArguments = [
        'Tag',
        'MaxWords',
        'MinWords',
        'ShortWord',
        'HighlightAll',
        'MaxFragments',
        'FragmentDelimiter'
    ];

    /**
     * @var array|\string[][]
     */
    protected array $additionalAccessories = [
        'orderDirections' => ['nulls last', 'nulls first']
    ];

    /**
     * PostgreSqlDriver constructor.
     */
    public function __construct()
    {
        $this->initializeDriverGrammaticalStructure();
    }

    /**
     * @return array
     */
    public function getWeights(): array
    {
        return $this->weights;
    }

    /**
     * @return array
     */
    public function getNormalizationBitmasks(): array
    {
        return $this->normalizationBitmasks;
    }

    /**
     * @return array
     */
    public function getHighlightingArguments(): array
    {
        return $this->highlightingArguments;
    }
}