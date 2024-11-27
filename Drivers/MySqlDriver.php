<?php

namespace Moirai\Drivers;

use Moirai\DDL\Constraints\ColumnConstraints;
use Moirai\DDL\Constraints\TableConstraints;
use Moirai\DDL\DataTypes;
use Moirai\DDL\ForeignKeyActions;

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
     * @var array
     */
    private static array $allowedForeignKeyActions = [
        ForeignKeyActions::CASCADE,
        ForeignKeyActions::SET_NULL,
        ForeignKeyActions::RESTRICT,
        ForeignKeyActions::NO_ACTION
    ];

    /**
     * MySqlDriver constructor.
     */
    public function __construct()
    {
        $this->initializeDriverGrammaticalStructure();
    }
}
