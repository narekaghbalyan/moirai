<?php

namespace Moirai\Drivers;

use Moirai\DDL\Constraints;
use Moirai\DDL\DataTypes;

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
     * @var array|string[]
     */
    protected array $dataTypes = [
        DataTypes::SMALL_INTEGER => 'SMALLINT',                // 2 bytes
        DataTypes::INTEGER => 'INTEGER',                  // 4 bytes
        DataTypes::BIG_INTEGER => 'BIGINT',                    // 8 bytes
        DataTypes::DECIMAL => 'DECIMAL{precision_and_scale}', // Exact numeric with selectable precision
        DataTypes::NUMERIC => 'NUMERIC{precision_and_scale}', // Exact numeric
        DataTypes::FLOAT => 'FLOAT',
        DataTypes::REAL => 'REAL',                        // 4 bytes floating point
        DataTypes::DOUBLE => 'DOUBLE PRECISION', // 8 bytes floating point
        DataTypes::MONEY => 'MONEY',                      // Currency type
        DataTypes::CHAR => 'CHAR{length}',                     // Fixed-length character
        DataTypes::VARCHAR => 'VARCHAR{length}',                // Variable-length character
        DataTypes::TEXT => 'TEXT',                        // Variable-length character with no specific length
        DataTypes::BYTEA => 'BYTEA',                      // Binary data


        DataTypes::DATE => 'DATE',                        // Date type
        DataTypes::TIME => 'TIME{precision}',                        // Time without time zone
        DataTypes::TIMESTAMP => 'TIMESTAMP{precision}',              // Timestamp without time zone
        DataTypes::TIME_TZ => 'TIME{precision} WITH TIME ZONE',      // Time with time zone
        DataTypes::TIMESTAMP_TZ => 'TIMESTAMP{precision} WITH TIME ZONE', // Timestamp with time zone


        DataTypes::INTERVAL => 'INTERVAL',                // Time interval
        DataTypes::BOOLEAN => 'BOOLEAN',                   // Boolean type
        DataTypes::UUID => 'UUID',                        // Universally Unique Identifier
        DataTypes::JSON => 'JSON',                        // JSON data type
        DataTypes::JSONB => 'JSONB',                      // Binary JSON data type
        DataTypes::XML => 'XML',                          // XML data type
        DataTypes::ARRAY => '[]',                // Array type (e.g., INTEGER[])
        DataTypes::HSTORE => 'HSTORE',                    // Key-value pairs
        DataTypes::INET => 'INET',                        // IP address
        DataTypes::CIDR => 'CIDR',                        // IP subnet
        DataTypes::POINT => 'POINT',                      // Geometric point
        DataTypes::LINE => 'LINE',                        // Geometric line
        DataTypes::LSEG => 'LSEG',                        // Line segment
        DataTypes::BOX => 'BOX',                          // Geometric box
        DataTypes::POLYGON => 'POLYGON',                  // Geometric polygon
        DataTypes::CIRCLE => 'CIRCLE',                    // Geometric circle
    ];

    private array $constraints = [
        Constraints::CHECK => 'CHECK({column} >= 0)',
        Constraints::AUTOINCREMENT => 'SERIAL',
        Constraints::NOT_NULL => 'NOT NULL',
        Constraints::UNIQUE => 'UNIQUE',
        Constraints::DEFAULT => 'DEFAULT "{value}"',
        Constraints::COLLATION => 'COLLATE {value}',
        Constraints::PRIMARY_KEY => 'PRIMARY KEY',
        Constraints::FOREIGN_KEY => 'FOREIGN KEY ({column}) REFERENCES {table}({column})',
        Constraints::ON_UPDATE => 'ON UPDATE {action}',
        Constraints::ON_DELETE => 'ON DELETE {action}',
        Constraints::INDEX => 'INDEX {name} ({column})',
        Constraints::COMMENT => 'COMMENT ON COLUMN {table}.{column} IS \'{value}\''
    ];

    /**
     * @var array
     */
    protected array $pitaForStrings = [
        'opening' => '\'',
        'closing' => '\''
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
    protected array $dmlAdditionalAccessories = [
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
