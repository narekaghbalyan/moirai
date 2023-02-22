<?php

namespace Moarai\Drivers;

class MariaDbDriver extends Driver
{
    public function __construct()
    {
        $this->initializeDriver();
    }

    public function initializeDriverLexicalStructure(): void
    {
        $this->setPitaForColumns('`');

        $this->setPitaForStrings('\'');
    }
}