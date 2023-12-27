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
        'char'               => 'CHAR',
        'string'             => 'VARCHAR',
        'tinyText'           => 'TINYTEXT',
        'text'               => 'TEXT',
        'mediumText'         => 'MEDIUMTEXT',
        'longText'           => 'LONGTEXT',
        'tinyblob'           => 'TINYBLOB',
        'blob'               => 'BLOB',
        'mediumBlob'         => 'MEDIUMBLOB',
        'longBlob'           => 'LONGBLOB',
        'bit'                => 'BIT',
        'integer'            => 'INT',
        'tinyInteger'        => 'TINYINT',
        'smallInteger'       => 'SMALLINT',
        'mediumInteger'      => 'MEDIUMINT',
        'bigInteger'         => 'BIGINT',
        'float'              => 'FLOAT',
        'double'             => 'DOUBLE',
        'decimal'            => 'DECIMAL',
        'boolean'            => 'BOOLEAN',
        'enum'               => 'ENUM',
        'set'                => 'SET',
        'json'               => 'JSON',
        'jsonb'              => 'JSONB',
        'date'               => 'DATE',
        'dateTime'           => 'DATETIME',
        'time'               => 'TIME',
        'timestamp'          => 'TIMESTAMP',
        'year'               => 'YEAR',
        'binary'             => 'BINARY',
        'varbinary'          => 'VARBINARY',
        'geometry'           => 'GEOMETRY',
        'point'              => 'POINT',
        'lineString'         => 'LINESTRING',
        'polygon'            => 'POLYGON',
        'multipoint'         => 'MULTIPOINT',
        'multiLineString'    => 'MULTILINESTRING',
        'multiPolygon'       => 'MULTIPOLYGON',
        'geometryCollection' => 'GEOMETRYCOLLECTION'
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