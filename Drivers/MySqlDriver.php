<?php

namespace Moarai\Drivers;

class MySqlDriver extends Driver
{
    public function __construct()
    {
        $this->initializeDriver();
    }

    public function initializeDriverLexicalStructure(): void
    {
        $this->setPitaForColumns('`');

        $this->setPitaForStrings('\'');
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
            'doublePrecision' => 'DOUBLE PRECISION',
            'decimal' => 'DECIMAL',
            'boolean' => 'BOOLEAN',
            'enum' => 'ENUM',
            'set' => 'SET',
            'json' => 'JSON',
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