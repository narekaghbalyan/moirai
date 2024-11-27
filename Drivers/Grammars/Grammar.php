<?php

namespace Moirai\Drivers\Grammars;

abstract class Grammar
{
    /**
     * @var array
     */
    protected array $pitaForColumns;

    /**
     * @var array
     */
    protected array $pitaForStrings;

    /**
     * @return array
     */
    public function getPitaForColumns(): array
    {
        return $this->pitaForColumns;
    }

    /**
     * @return array
     */
    public function getPitaForStrings(): array
    {
        return $this->pitaForStrings;
    }
}