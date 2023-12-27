<?php

namespace Moirai\Drivers;

use Exception;
use Moirai\Drivers\Grammars\DriverGrammar;

abstract class Driver
{
    /**
     * @var DriverGrammar
     */
    protected DriverGrammar $grammar;

    /**
     * @var array
     */
    protected array $pitaForColumns;

    /**
     * @var array
     */
    protected array $pitaForStrings;

    /**
     * @var array
     */
    protected array $dataTypes;

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

    /**
     * @return array
     */
    public function getDataTypes(): array
    {
        return $this->dataTypes;
    }

    /**
     * @return array|null
     */
    public function getAdditionalAccessories(): array|null
    {
        return $this->additionalAccessories ?? null;
    }

    /**
     * @param string $dataTypeKey
     * @return string
     * @throws Exception
     */
    public function getDataType(string $dataTypeKey): string
    {
        if (!isset($this->dataTypes[$dataTypeKey])) {
            throw new Exception('This data type is not supported by this driver.');
        }

        return $this->dataTypes[$dataTypeKey];
    }

    /**
     * @return string
     */
    public function getDriverName(): string
    {
        return AvailableDbmsDrivers::getDrivers()[strtoupper($this->getCleanDbmsName())];
    }

    protected function initializeDriverGrammaticalStructure(): void
    {
        $grammarPath = __NAMESPACE__ . '\\' . 'Grammars' . '\\' . $this->getCleanDbmsName() . 'Grammar';

        $this->grammar = new $grammarPath();
    }

    /**
     * @return string
     */
    private function getCleanDbmsName(): string
    {
        return str_replace([__NAMESPACE__, '/', '\\', 'DriverInterface'], '', get_called_class());
    }
}