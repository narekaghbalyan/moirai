<?php

namespace Moarai\Drivers;

class PostgreSqlDriver extends Driver
{
    protected array $normalizationBitmasks = [0, 1, 2, 4, 8, 16, 32];

    protected array $weights = ['A', 'B', 'C', 'D'];

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
}