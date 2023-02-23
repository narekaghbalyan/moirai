<?php

namespace Moarai\Drivers;

class OracleDriver extends Driver
{
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