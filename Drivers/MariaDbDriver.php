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
        DataTypes::BOOLEAN => 'BOOLEAN',

        DataTypes::TINY_INTEGER => 'TINYINT',
        DataTypes::SMALL_INTEGER => 'SMALLINT',
        DataTypes::MEDIUM_INTEGER => 'MEDIUMINT',
        DataTypes::INTEGER => 'INT',
        DataTypes::BIG_INTEGER => 'BIGINT',

        DataTypes::FLOAT => 'FLOAT',
        DataTypes::DOUBLE => 'DOUBLE',
        DataTypes::DECIMAL => 'DECIMAL',

        DataTypes::CHAR => 'CHAR',
        DataTypes::VARCHAR => 'VARCHAR',
        DataTypes::TINY_TEXT => 'TINYTEXT',
        DataTypes::TEXT => 'TEXT',
        DataTypes::MEDIUM_TEXT => 'MEDIUMTEXT',
        DataTypes::LONG_TEXT => 'LONGTEXT',
        DataTypes::TINY_BLOB => 'TINYBLOB',
        DataTypes::BLOB => 'BLOB',
        DataTypes::MEDIUM_BLOB => 'MEDIUMBLOB',
        DataTypes::LONG_BLOB => 'LONGBLOB',
        DataTypes::SET => 'SET',
        DataTypes::BINARY => 'BINARY',
        DataTypes::VARBINARY => 'VARBINARY',
        DataTypes::ENUM => 'ENUM',
        DataTypes::IP_V4 => 'INET4',
        DataTypes::IP_V6 => 'INET6',
        DataTypes::ROW => 'ROW',
        DataTypes::UUID => 'UUID',

        DataTypes::DATE => 'DATE',
        DataTypes::DATE_TIME => 'DATETIME',
        DataTypes::TIME => 'TIME',
        DataTypes::TIMESTAMP => 'TIMESTAMP',
        DataTypes::YEAR => 'YEAR',

        DataTypes::BIT => 'BIT',

        DataTypes::GEOMETRY => 'GEOMETRY',
        DataTypes::GEOMETRY_COLLECTION => 'GEOMETRYCOLLECTION',
        DataTypes::POINT => 'POINT',
        DataTypes::MULTI_POINT => 'MULTIPOINT',
        DataTypes::LINE_STRING => 'LINESTRING',
        DataTypes::MULTI_LINE_STRING => 'MULTILINESTRING',
        DataTypes::POLYGON => 'POLYGON',
        DataTypes::MULTI_POLYGON => 'MULTIPOLYGON'
    ];

    /**
     * MariaDbDriver constructor.
     */
    public function __construct()
    {
        $this->initializeDriverGrammaticalStructure();
    }
}
