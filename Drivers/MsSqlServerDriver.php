<?php

namespace Moirai\Drivers;

class MsSqlServerDriver extends Driver
{
    public function __construct()
    {
        $this->initializeDriver();
    }

    public function initializeDriverLexicalStructure(): void
    {
        $this->setPitaForColumns('[', ']');

        $this->setPitaForStrings('\'');
    }
}