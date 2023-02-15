<?php

namespace Moarai\Drivers;

class PostgreSqlDriver extends Driver
{
    protected array $normalizationBitmasks = [0, 1, 2, 4, 8, 16, 32];

    protected array $weights = ['A', 'B', 'C', 'D'];

    protected array $highlightingArguments = [
        'Tag',
        'MaxWords',
        'MinWords',
        'ShortWord',
        'HighlightAll',
        'MaxFragments',
        'FragmentDelimiter'
    ];

    protected array $additionalAccessories = [
        'orderDirections' => ['nulls last', 'nulls first']
    ];

    public function __construct()
    {
        $this->initializeDriver();
    }

    public function initializeDriverLexicalStructure(): void
    {
        $this->setPitaForColumns('"');

        $this->setPitaForStrings('\'');
    }

    public function getWeights(): array
    {
        return $this->weights;
    }

    public function getNormalizationBitmasks(): array
    {
        return $this->normalizationBitmasks;
    }

    public function getHighlightingArguments(): array
    {
        return $this->highlightingArguments;
    }
}