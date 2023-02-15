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

    public function setPitaForColumns(string $pita): void
    {
        $this->pitaForColumns = $pita;
    }

    public function setPitaForStrings(string $pita): void
    {
        $this->pitaForStrings = $pita;
    }

    public function initializeDriver(): void
    {
        $this->initializeDriverLexicalStructure();
    }

    abstract function initializeDriverLexicalStructure(): void;

    public function getDriverName(): string
    {
        $driver = str_replace([__NAMESPACE__, '/', '\\', 'Driver'], '', get_called_class());

        $driver = strtoupper($driver);

        return AvailableDbmsDrivers::getDrivers()[$driver];
    }

    public function getAdditionalAccessories(): array|null
    {
        return $this->additionalAccessories ?? null;
    }
}