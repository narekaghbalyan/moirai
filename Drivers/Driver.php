<?php

namespace Moirai\Drivers;

use Exception;

abstract class Driver
{
    protected $grammar;

    protected array $pitaForColumns = [];

    protected array $pitaForStrings = [];

    protected array $dataTypes = [];

    public function getDataType(string $dataTypeKey): string
    {
        if (!isset($this->dataTypes[$dataTypeKey])) {
            throw new Exception('This data type is not supported by this driver.');
        }

        return $this->dataTypes[$dataTypeKey];
    }

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

        $this->initializeDriverGrammaticalStructure();

        $this->initializeDriverDataTypes();
    }

    abstract function initializeDriverLexicalStructure(): void;

    abstract function initializeDriverDataTypes(): void;

    public function initializeDriverGrammaticalStructure(): void
    {
        $grammarsNamespace = __NAMESPACE__ . '\\' . 'Grammars';

        $grammarName = $this->getCleanDbmsName() . 'Grammar';

        $grammarPath = $grammarsNamespace . '\\' . $grammarName;

        $this->grammar = new $grammarPath();
    }

    public function getDriverName(): string
    {
        $driver = $this->getCleanDbmsName();

        $driver = strtoupper($driver);

        return AvailableDbmsDrivers::getDrivers()[$driver];
    }

    public function getAdditionalAccessories(): array|null
    {
        return $this->additionalAccessories ?? null;
    }

    private function getCleanDbmsName(): string
    {
        return str_replace([__NAMESPACE__, '/', '\\', 'Driver'], '', get_called_class());
    }
}