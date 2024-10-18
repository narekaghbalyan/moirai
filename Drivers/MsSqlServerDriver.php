<?php

namespace Moirai\Drivers;

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
        'int' => 'int',
        'smallint' => 'smallint',
        'tinyint' => 'tinyint',
        'bigint' => 'bigint',
        'decimal' => 'decimal(p, s)',
        'numeric' => 'numeric(p, s)',
        'money' => 'money',
        'smallmoney' => 'smallmoney',
        'float' => 'float',
        'real' => 'real',
        'char' => 'char(n)',
        'varchar' => 'varchar(n)',
        'text' => 'text',
        'nchar' => 'nchar(n)',
        'nvarchar' => 'nvarchar(n)',
        'ntext' => 'ntext',
        'binary' => 'binary(n)',
        'varbinary' => 'varbinary(n)',
        'image' => 'image',
        'date' => 'date',
        'time' => 'time',
        'datetime' => 'datetime',
        'datetime2' => 'datetime2',
        'smalldatetime' => 'smalldatetime',
        'datetimeoffset' => 'datetimeoffset',
        'bit' => 'bit',
        'uniqueidentifier' => 'uniqueidentifier',
        'xml' => 'xml',
        'json' => 'nvarchar(max)', // JSON stored as nvarchar
        'sql_variant' => 'sql_variant',
        'rowversion' => 'rowversion',
        'timestamp' => 'timestamp',
        'geometry' => 'geometry',
        'geography' => 'geography',
        'hierarchyid' => 'hierarchyid'
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