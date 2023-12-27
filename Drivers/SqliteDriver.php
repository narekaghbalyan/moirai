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
     * SqliteDriver constructor.
     */
    public function __construct()
    {
        $this->initializeDriverGrammaticalStructure();
    }
}