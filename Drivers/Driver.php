<?php

namespace Moarai\Drivers;

abstract class Driver
{
    protected string $pitaForColumns;

    protected string $pitaForStrings;

    public function getPitaForColumns(): string
    {
        return $this->pitaForColumns;
    }

    public function getPitaForStrings(): string
    {
        return $this->pitaForStrings;
    }

    public function setPitaForColumns(string $pita): string
    {
        $this->pitaForColumns = $pita;
    }

    public function setPitaForStrings(string $pita): string
    {
        $this->pitaForStrings = $pita;
    }

    abstract function initializeDriver(): void;
}