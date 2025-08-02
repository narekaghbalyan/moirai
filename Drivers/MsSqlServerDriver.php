<?php

namespace Moirai\Drivers;

use Moirai\DDL\Shared\ForeignKeyActions;
use Moirai\Drivers\Grammars\MsSqlServerGrammar;
use Moirai\Drivers\Lexises\MsSqlServerLexis;

class MsSqlServerDriver extends Driver implements DriverInterface
{
    /**
     * @var string
     */
    protected string $name = 'MS SQL Server';

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
     * MsSqlServerDriver constructor.
     */
    public function __construct()
    {
        $this->grammar = new MsSqlServerGrammar();
        $this->lexis = new MsSqlServerLexis();
    }
}
