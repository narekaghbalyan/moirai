<?php

namespace Moirai\Drivers;

use Moirai\DDL\Constraints;
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
        DataTypes::TINY_INTEGER => 'TINYINT',
        DataTypes::SMALL_INTEGER => 'SMALLINT',
        DataTypes::MEDIUM_INTEGER => 'MEDIUMINT',
        DataTypes::INTEGER => 'INT',
        DataTypes::BIG_INTEGER => 'BIGINT',
        DataTypes::FLOAT => 'FLOAT{precision}',
        DataTypes::DOUBLE => 'DOUBLE{precision}',
        DataTypes::DECIMAL => 'DECIMAL{precision_and_scale}',
        DataTypes::NUMERIC => 'NUMERIC{precision_and_scale}',
        DataTypes::BIT => 'BIT{size}',
        DataTypes::CHAR => 'CHAR{length}',
        DataTypes::VARCHAR => 'VARCHAR{length}',
        DataTypes::TINY_TEXT => 'text',
        DataTypes::TEXT => 'text',
        DataTypes::MEDIUM_TEXT => 'text',
        DataTypes::LONG_TEXT => 'text',
        DataTypes::BINARY => 'BINARY(length)',
        DataTypes::VARBINARY => 'VARBINARY(length)',
        DataTypes::TINY_BLOB => 'BLOB',
        DataTypes::BLOB => 'BLOB',
        DataTypes::MEDIUM_BLOB => 'BLOB',
        DataTypes::LONG_BLOB => 'BLOB',


        DataTypes::DATE => 'DATE',
        DataTypes::DATE_TIME => 'DATETIME',
        DataTypes::TIMESTAMP => 'TIMESTAMP{precision}',
        DataTypes::TIME => 'TIME{precision}',
        DataTypes::YEAR => 'YEAR',


        DataTypes::ENUM => 'ENUM{white_list}',
        DataTypes::SET => 'SET{white_list}',
        DataTypes::JSON => 'JSON',
        DataTypes::POINT => 'POINT',
        DataTypes::LINE_STRING => 'LINESTRING',
        DataTypes::POLYGON => 'POLYGON',
        DataTypes::GEOMETRY => 'GEOMETRY',
        DataTypes::GEOMETRY_COLLECTION => 'GEOMETRYCOLLECTION',
        DataTypes::MULTI_POINT => 'MULTIPOINT',
        DataTypes::MULTI_LINE_STRING => 'MULTILINESTRING',
        DataTypes::MULTI_POLYGON => 'MULTIPOLYGON'
    ];

    private array $constraints = [
        Constraints::UNSIGNED => 'UNSIGNED',
        Constraints::CHECK => 'CHECK({column} >= 0)',
        Constraints::AUTOINCREMENT => 'AUTO_INCREMENT',
        Constraints::NOT_NULL => 'NOT NULL',
        Constraints::UNIQUE => 'UNIQUE',
        Constraints::DEFAULT => 'DEFAULT "{value}"',
        Constraints::COLLATION => 'COLLATE {value}',
        Constraints::CHARSET => 'CHARACTER SET {value}',
        Constraints::PRIMARY_KEY => 'PRIMARY KEY',
        Constraints::FOREIGN_KEY => 'FOREIGN KEY ({column}) REFERENCES {table}({column})',
        Constraints::ON_UPDATE => 'ON UPDATE {action}',
        Constraints::ON_DELETE => 'ON DELETE {action}',
        Constraints::INVISIBLE => 'INVISIBLE',
        Constraints::INDEX => 'INDEX {name} ({column})',
        Constraints::COMMENT => 'COMMENT "{value}"'
    ];

    /**
     * MariaDbDriver constructor.
     */
    public function __construct()
    {
        $this->initializeDriverGrammaticalStructure();
    }
}
