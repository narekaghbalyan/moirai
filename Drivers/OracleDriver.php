<?php

namespace Moirai\Drivers;

use Moirai\DDL\Constraints\ColumnConstraints;
use Moirai\DDL\Constraints\TableConstraints;
use Moirai\DDL\DataTypes;
use Moirai\DDL\ForeignKeyActions;

class OracleDriver extends Driver
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
     * @var array
     */
    public static array $allowedForeignKeyActions = [
        ForeignKeyActions::CASCADE,
        ForeignKeyActions::SET_NULL,
        ForeignKeyActions::SET_DEFAULT,
        ForeignKeyActions::NO_ACTION
    ];

    /**
     * OracleDriver constructor.
     */
    public function __construct()
    {
        $this->initializeDriverGrammaticalStructure();
    }
}
