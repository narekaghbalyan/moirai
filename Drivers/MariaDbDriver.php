<?php

namespace Moirai\Drivers;

use Moirai\DDL\DataTypes;

class MariaDbDriver extends Driver
{
    /**
     * @var array
     */
    protected array $pitaForColumns = [
        'opening' => '`',
        'closing' => '`'
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
        'TINYINT' => 'TINYINT',
        'SMALLINT' => 'SMALLINT',
        'MEDIUMINT' => 'MEDIUMINT',
        'INT' => 'INT',
        'BIGINT' => 'BIGINT',
        'FLOAT' => 'FLOAT',
        'DOUBLE' => 'DOUBLE',
        'DECIMAL' => 'DECIMAL(p, s)',
        'NUMERIC' => 'NUMERIC(p, s)',
        'BIT' => 'BIT',
        'CHAR' => 'CHAR(n)',
        'VARCHAR' => 'VARCHAR(n)',
        'TEXT' => 'TEXT',
        'BINARY' => 'BINARY(n)',
        'VARBINARY' => 'VARBINARY(n)',
        'BLOB' => 'BLOB',
        'DATE' => 'DATE',
        'DATETIME' => 'DATETIME',
        'TIMESTAMP' => 'TIMESTAMP',
        'TIME' => 'TIME',
        'YEAR' => 'YEAR',
        'ENUM' => 'ENUM(val1, val2, ...)',
        'SET' => 'SET(val1, val2, ...)',
        'JSON' => 'JSON',
        'POINT' => 'POINT',
        'LINESTRING' => 'LINESTRING',
        'POLYGON' => 'POLYGON',
        'GEOMETRY' => 'GEOMETRY',
        'GEOMETRYCOLLECTION' => 'GEOMETRYCOLLECTION',
        'MULTIPOINT' => 'MULTIPOINT',
        'MULTILINESTRING' => 'MULTILINESTRING',
        'MULTIPOLYGON' => 'MULTIPOLYGON',
    ];

    /**
     * MariaDbDriver constructor.
     */
    public function __construct()
    {
        $this->initializeDriverGrammaticalStructure();
    }
}
