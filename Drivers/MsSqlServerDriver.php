<?php

namespace Moirai\Drivers;

use Moirai\DDL\Accessories;
use Moirai\DDL\DataTypes;

class MsSqlServerDriver extends Driver
{
    /**
     * @var array
     */
    protected array $pitaForColumns = [
        'opening' => '[',
        'closing' => ']'
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
        DataTypes::INTEGER => 'int',
        DataTypes::SMALL_INTEGER => 'smallint',
        DataTypes::TINY_INTEGER => 'tinyint',
        DataTypes::BIG_INTEGER => 'bigint',
        DataTypes::DECIMAL => 'decimal{precision_and_scale}',
        DataTypes::NUMERIC => 'numeric{precision_and_scale}',
        DataTypes::MONEY => 'money',
        DataTypes::SMALL_MONEY => 'smallmoney',
        DataTypes::FLOAT => 'float{precision}',
        DataTypes::REAL => 'real',
        DataTypes::CHAR => 'char{length}',
        DataTypes::VARCHAR => 'varchar{length}',
        DataTypes::TEXT => 'text',
        DataTypes::N_CHAR => 'nchar{length}',
        DataTypes::N_VARCHAR => 'nvarchar{length}',
        DataTypes::N_TEXT => 'ntext',
        DataTypes::BINARY => 'binary{length}',
        DataTypes::VARBINARY => 'varbinary(length)',
        DataTypes::IMAGE => 'image',

        DataTypes::DATE => 'date',
        DataTypes::TIME => 'time{precision}',
        DataTypes::DATE_TIME => 'datetime',
        DataTypes::DATE_TIME_2 => 'datetime2{precision}',
        DataTypes::SMALL_DATE_TIME => 'smalldatetime',
        DataTypes::DATE_TIME_OFFSET => 'datetimeoffset{precision}',


        DataTypes::BIT => 'bit',
        DataTypes::UUID => 'uniqueidentifier',
        DataTypes::XML => 'xml',
        DataTypes::JSON => 'nvarchar(max)', // JSON stored as nvarchar
        DataTypes::SQL_VARIANT => 'sql_variant',
        DataTypes::ROW_VERSION => 'rowversion',
        DataTypes::GEOMETRY => 'geometry',
        DataTypes::GEOGRAPHY => 'geography',
        DataTypes::HIERARYCHYID => 'hierarchyid'
    ];

    private array $accessories = [
        Accessories::UNSIGNED => 'CHECK({column} >= 0)',
        Accessories::AUTOINCREMENT => 'IDENTITY',
        Accessories::PRIMARY => 'PRIMARY KEY',
        Accessories::NULLABLE => 'NULL',
        Accessories::UNIQUE => 'UNIQUE',
        Accessories::DEFAULT => 'DEFAULT "{value}"',
        Accessories::COLLATION => 'COLLATE {value}',
        Accessories::INDEX => 'INDEX {name} ({column})'
    ];


    /**
     * @var bool
     */
    protected bool $useUnderscoreInDriverNameWhenSeparating = true;

    /**
     * MsSqlServerDriver constructor.
     */
    public function __construct()
    {
        $this->initializeDriverGrammaticalStructure();
    }
}
