<?php

namespace Moirai\Drivers;

use Moirai\DDL\ForeignKeyActions;
use Moirai\Drivers\Grammars\PostgreSqlGrammar;
use Moirai\Drivers\Lexises\PostgreSqlLexis;

class PostgreSqlDriver extends Driver implements DriverInterface
{
    /**
     * @var string
     */
    protected string $name = 'PostgreSQL';

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
     * @var array|\string[][]|null
     */
    protected array|null $additionalAccessories = [
        'orderDirections' => ['nulls last', 'nulls first']
    ];

    /**
     * @var array|int[]
     */
    private array $normalizationBitmasks = [0, 1, 2, 4, 8, 16, 32];

    /**
     * @var array|string[]
     */
    private array $weights = ['A', 'B', 'C', 'D'];

    /**
     * @var array|string[]
     */
    private array $highlightingArguments = [
        'Tag',
        'MaxWords',
        'MinWords',
        'ShortWord',
        'HighlightAll',
        'MaxFragments',
        'FragmentDelimiter'
    ];

    /**
     * PostgreSqlDriver constructor.
     */
    public function __construct()
    {
        $this->grammar = new PostgreSqlGrammar();
        $this->lexis = new PostgreSqlLexis();
    }

    /**
     * @return array
     */
    public function getWeights(): array
    {
        return $this->weights;
    }

    /**
     * @return array
     */
    public function getNormalizationBitmasks(): array
    {
        return $this->normalizationBitmasks;
    }

    /**
     * @return array
     */
    public function getHighlightingArguments(): array
    {
        return $this->highlightingArguments;
    }
}
