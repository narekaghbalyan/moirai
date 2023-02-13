<?php

namespace Moarai\Drivers;

class PostgreSqlDriver extends Driver
{
    public array $normalizationBitmasks = [0, 1, 2, 4, 8, 16, 32];

    public function __construct()
    {
        $this->initializeDriver();
    }

    public function initializeDriverLexicalStructure(): void
    {
        $this->setPitaForColumns('"');

        $this->setPitaForStrings('\'');
    }
}