<?php

namespace Moirai\Drivers;

class PostgreSqlDriver extends Driver
{
    protected array $normalizationBitmasks = [0, 1, 2, 4, 8, 16, 32];

    protected array $weights = ['A', 'B', 'C', 'D'];

    protected array $highlightingArguments = [
        'Tag',
        'MaxWords',
        'MinWords',
        'ShortWord',
        'HighlightAll',
        'MaxFragments',
        'FragmentDelimiter'
    ];

    protected array $additionalAccessories = [
        'orderDirections' => ['nulls last', 'nulls first']
    ];

    public function __construct()
    {
        $this->initializeDriver();
    }

    public function initializeDriverLexicalStructure(): void
    {
        $this->setPitaForColumns('"');

        $this->setPitaForStrings('\'');
    }

    public function getWeights(): array
    {
        return $this->weights;
    }

    public function getNormalizationBitmasks(): array
    {
        return $this->normalizationBitmasks;
    }

    public function getHighlightingArguments(): array
    {
        return $this->highlightingArguments;
    }

    public function initializeDriverDataTypes(): void
    {
        $this->dataTypes = [
            'char' => 'CHAR',
            'string' => 'VARCHAR',
            'tinyText' => 'TINYTEXT',
            'text' => 'TEXT',
            'mediumText' => 'MEDIUMTEXT',
            'longText' => 'LONGTEXT',
            'tinyblob' => 'TINYBLOB',
            'blob' => 'BLOB',
            'mediumBlob' => 'MEDIUMBLOB',
            'longBlob' => 'LONGBLOB',
            'bit' => 'BIT',
            'integer' => 'INT',
            'tinyInteger' => 'TINYINT',
            'smallInteger' => 'SMALLINT',
            'mediumInteger' => 'MEDIUMINT',
            'bigInteger' => 'BIGINT',
            'float' => 'FLOAT',
            'double' => 'DOUBLE',
            'decimal' => 'DECIMAL',
            'boolean' => 'BOOLEAN',
            'enum' => 'ENUM',
            'set' => 'SET',
            'json' => 'JSON',
            'jsonb' => 'JSONB',
            'date' => 'DATE',
            'dateTime' => 'DATETIME',
            'time' => 'TIME',
            'timestamp' => 'TIMESTAMP',
            'year' => 'YEAR',
            'binary' => 'BINARY',
            'varbinary' => 'VARBINARY',
            'geometry' => 'GEOMETRY',
            'point' => 'POINT',
            'lineString' => 'LINESTRING',
            'polygon' => 'POLYGON',
            'multipoint' => 'MULTIPOINT',
            'multiLineString' => 'MULTILINESTRING',
            'multiPolygon' => 'MULTIPOLYGON',
            'geometryCollection' => 'GEOMETRYCOLLECTION'
        ];
    }
}