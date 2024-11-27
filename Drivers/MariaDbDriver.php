<?php

namespace Moirai\Drivers;

use Moirai\DDL\ForeignKeyActions;
use Moirai\Drivers\Grammars\MariaDbGrammar;
use Moirai\Drivers\Lexises\MariaDbLexis;

class MariaDbDriver extends Driver implements DriverInterface
{
    /**
     * @var string
     */
    protected string $name = 'MariaDB';

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
     * MariaDbDriver constructor.
     */
    public function __construct()
    {
        $this->grammar = new MariaDbGrammar();
        $this->lexis = new MariaDbLexis();
    }
}
