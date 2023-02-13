<?php

namespace Moarai\Drivers;

class MySqlDriver extends Driver
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