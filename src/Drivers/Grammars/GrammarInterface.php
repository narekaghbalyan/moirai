<?php

namespace Moirai\Drivers\Grammars;

interface GrammarInterface
{
    /**
     * @return array
     */
    public function getPitaForColumns(): array;

    /**
     * @return array
     */
    public function getPitaForStrings(): array;
}