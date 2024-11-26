<?php

namespace Moirai\Drivers;

use Moirai\DDL\Constraints\ColumnConstraints;
use Moirai\DDL\Constraints\TableConstraints;
use Moirai\DDL\DataTypes;
use Moirai\DDL\ForeignKeyActions;

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
     * @var bool
     */
    protected bool $useUnderscoreInDriverNameWhenSeparating = true;

    /**
     * @var array|string[]
     */
    protected array $dataTypes = [
        DataTypes::INTEGER => 'INT',
        DataTypes::SMALL_INTEGER => 'SMALLINT',
        DataTypes::TINY_INTEGER => 'TINYINT',
        DataTypes::BIG_INTEGER => 'BIGINT',
        DataTypes::DECIMAL => 'DECIMAL({precision_and_scale})',
        DataTypes::NUMERIC => 'NUMERIC({precision_and_scale})',
        DataTypes::MONEY => 'MONEY',
        DataTypes::SMALL_MONEY => 'SMALLMONEY',
        DataTypes::FLOAT => 'FLOAT({precision})',
        DataTypes::REAL => 'REAL',
        DataTypes::CHAR => 'CHAR({length})',
        DataTypes::VARCHAR => 'VARCHAR({length})',
        DataTypes::TEXT => 'TEXT',
        DataTypes::N_CHAR => 'NCHAR({length})',
        DataTypes::N_VARCHAR => 'NVARCHAR({length})',
        DataTypes::N_TEXT => 'NTEXT',
        DataTypes::BINARY => 'BINARY({length})',
        DataTypes::VARBINARY => 'VARBINARY({length})',
        DataTypes::IMAGE => 'IMAGE',
        DataTypes::DATE => 'DATE',
        DataTypes::TIME => 'TIME({precision})',
        DataTypes::DATE_TIME => 'DATETIME',
        DataTypes::DATE_TIME_2 => 'DATETIME2({precision})',
        DataTypes::SMALL_DATE_TIME => 'SMALLDATETIME',
        DataTypes::DATE_TIME_OFFSET => 'DATETIMEOFFSET({precision})',
        DataTypes::BIT => 'BIT',
        DataTypes::UUID => 'UNIQUEIDENTIFIER',
        DataTypes::XML => 'XML',
        DataTypes::JSON => 'NVARCHAR({max})',
        DataTypes::SQL_VARIANT => 'SQL_VARIANT',
        DataTypes::ROW_VERSION => 'ROWVERSION',
        DataTypes::GEOMETRY => 'GEOMETRY',
        DataTypes::GEOGRAPHY => 'GEOGRAPHY',
        DataTypes::HIERARYCHYID => 'HIERARCHYID'
    ];

    /**
     * @var array|string[]
     */
    private array $columnConstraints = [
        ColumnConstraints::CHECK => 'CHECK({column} >= 0)',
        ColumnConstraints::AUTOINCREMENT => 'IDENTITY',
        ColumnConstraints::NOT_NULL => 'NOT NULL',
        ColumnConstraints::UNIQUE => 'UNIQUE',
        ColumnConstraints::DEFAULT => 'DEFAULT "{value}"',
        ColumnConstraints::COLLATION => 'COLLATE {value}',
        ColumnConstraints::PRIMARY_KEY => 'PRIMARY KEY',
    ];

    /**
     * @var array|string[]
     */
    private array $tableConstraints = [
        TableConstraints::CHECK => 'CONSTRAINT {name} CHECK({expression})',
        TableConstraints::UNIQUE => 'CONSTRAINT {name} UNIQUE({columns})',
        TableConstraints::PRIMARY_KEY => 'CONSTRAINT {name} PRIMARY KEY ({columns})',
        TableConstraints::FOREIGN_KEY => 'CONSTRAINT {name} FOREIGN KEY ({columns}) REFERENCES {referenced_table}({referenced_columns}) ON DELETE {on_delete_action} ON UPDATE {on_update_action}',
        TableConstraints::INDEX => 'INDEX {name} ({columns})'
    ];

    /**
     * @var array
     */
    public static array $allowedForeignKeyActions = [
        ForeignKeyActions::CASCADE,
        ForeignKeyActions::SET_NULL,
        ForeignKeyActions::SET_DEFAULT,
        ForeignKeyActions::NO_ACTION
    ];

    /**
     * MsSqlServerDriver constructor.
     */
    public function __construct()
    {
        $this->initializeDriverGrammaticalStructure();
    }
}
