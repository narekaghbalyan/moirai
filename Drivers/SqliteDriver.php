<?php

namespace Moirai\Drivers;

use Moirai\DDL\ForeignKeyActions;
use Moirai\Drivers\Grammars\SqliteGrammar;
use Moirai\Drivers\Lexises\SqliteLexis;

class SqliteDriver extends Driver implements DriverInterface
{
    /**
     * @var string
     */
    protected string $name = 'SQLite';

    /**
     * @var array
     */
    protected array $allowedForeignKeyActions = [
        ForeignKeyActions::CASCADE,
        ForeignKeyActions::SET_NULL,
        ForeignKeyActions::RESTRICT,
        ForeignKeyActions::SET_DEFAULT,
        ForeignKeyActions::NO_ACTION
    ];

    /**
     * SqliteDriver constructor.
     */
    public function __construct()
    {
        $this->grammar = new SqliteGrammar();
        $this->lexis = new SqliteLexis();
    }
}
