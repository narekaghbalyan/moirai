<?php

namespace Moirai\Drivers;

use Moirai\DDL\Shared\ForeignKeyActions;
use Moirai\Drivers\Grammars\MySqlGrammar;
use Moirai\Drivers\Lexises\MySqlLexis;

class MySqlDriver extends Driver implements DriverInterface
{
    /**
     * @var string
     */
    protected string $name = 'MySQL';

    /**
     * @var array
     */
    protected array $allowedForeignKeyActions = [
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
        $this->grammar = new MySqlGrammar();
        $this->lexis = new MySqlLexis();
    }
}
