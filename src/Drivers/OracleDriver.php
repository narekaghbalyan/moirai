<?php

namespace Moirai\Drivers;

use Moirai\DDL\Shared\ForeignKeyActions;
use Moirai\Drivers\Grammars\OracleGrammar;
use Moirai\Drivers\Lexises\OracleLexis;

class OracleDriver extends Driver implements DriverInterface
{
    /**
     * @var string
     */
    protected string $name = 'Oracle';

    /**
     * @var array
     */
    protected array $allowedForeignKeyActions = [
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
        $this->grammar = new OracleGrammar();
        $this->lexis = new OracleLexis();
    }
}
