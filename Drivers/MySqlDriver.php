<?php

namespace Moirai\Drivers;

use Moirai\DDL\DataTypes;

class MySqlDriver extends Driver
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
        DataTypes::TINY_INTEGER => 'TINYINT',
        DataTypes::SMALL_INTEGER => 'SMALLINT',
        DataTypes::MEDIUM_INTEGER => 'MEDIUMINT',
        DataTypes::INTEGER => 'INT',
        DataTypes::BIG_INTEGER => 'BIGINT',
        DataTypes::FLOAT => 'FLOAT',
        DataTypes::DOUBLE => 'DOUBLE',
        DataTypes::DECIMAL => 'DECIMAL(p, s)',
        DataTypes::NUMERIC => 'NUMERIC(p, s)',
        DataTypes::BIT => 'BIT',
        DataTypes::CHAR => 'CHAR(n)',
        DataTypes::VARCHAR => 'VARCHAR(n)',
        DataTypes::TINY_TEXT => 'text',
        DataTypes::TEXT => 'text',
        DataTypes::MEDIUM_TEXT => 'text',
        DataTypes::LONG_TEXT => 'text',
        DataTypes::BINARY => 'BINARY(n)',
        DataTypes::VARBINARY => 'VARBINARY(n)',
        DataTypes::TINY_BLOB => 'BLOB',
        DataTypes::BLOB => 'BLOB',
        DataTypes::MEDIUM_BLOB => 'BLOB',
        DataTypes::LONG_BLOB => 'BLOB',
        DataTypes::DATE => 'DATE',
        DataTypes::DATE_TIME => 'DATETIME',
        DataTypes::TIMESTAMP => 'TIMESTAMP',
        DataTypes::TIME => 'TIME',
        DataTypes::YEAR => 'YEAR',
        DataTypes::ENUM => 'ENUM(val1, val2, ...)',
        DataTypes::SET => 'SET(val1, val2, ...)',
        DataTypes::JSON => 'JSON',
        DataTypes::POINT => 'POINT',
        DataTypes::LINE_STRING => 'LINESTRING',
        DataTypes::POLYGON => 'POLYGON',
        DataTypes::GEOMETRY => 'GEOMETRY',
        DataTypes::GEOMETRY_COLLECTION => 'GEOMETRYCOLLECTION',
        DataTypes::MULTI_POINT => 'MULTIPOINT',
        DataTypes::MULTI_LINE_STRING => 'MULTILINESTRING',
        DataTypes::MULTI_POLYGON => 'MULTIPOLYGON',
    ];

    /**
     * MySqlDriver constructor.
     */
    public function __construct()
    {
        $this->initializeDriverGrammaticalStructure();
    }
}
