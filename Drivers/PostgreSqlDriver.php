<?php

namespace Moirai\Drivers;

use Moirai\DDL\Constraints\ColumnConstraints;
use Moirai\DDL\Constraints\TableConstraints;
use Moirai\DDL\DataTypes;
use Moirai\DDL\ForeignKeyActions;

class PostgreSqlDriver extends Driver
{
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
     * @var array|\string[][]
     */
    protected array $dmlAdditionalAccessories = [
        'orderDirections' => ['nulls last', 'nulls first']
    ];

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
     * PostgreSqlDriver constructor.
     */
    public function __construct()
    {
        $this->initializeDriverGrammaticalStructure();
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
