<?php

namespace Moarai\Drivers;

abstract class Driver
{
    protected array $pitaForColumns = [];

    protected array $pitaForStrings = [];

    public function getPitaForColumns(): array
    {
        return $this->pitaForColumns;
    }

    public function getPitaForStrings(): array
    {
        return $this->pitaForStrings;
    }

    public function setPitaForColumns(string $pita, string|null $closingPita = null): void
    {
        $this->setPita($pita, $closingPita, $this->pitaForColumns);
    }

    public function setPitaForStrings(string $pita, string|null $closingPita = null): void
    {
        $this->setPita($pita, $closingPita, $this->pitaForStrings);
    }

    private function setPita(string $pita, string|null $closingPita, array &$pitaContainer): void
    {
        if (empty($closingPita)) {
            $closingPita = $pita;
        }

        $pitaContainer = [
            'opening' => $pita,
            'closing' => $closingPita
        ];
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