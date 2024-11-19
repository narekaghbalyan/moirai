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
     * @var bool
     */
    protected bool $useUnderscoreInDriverNameWhenSeparating = false;

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
     * @return array
     */
    public function getDdlAccessories(): array
    {
        return $this->ddlAccessories;
    }

    /**
     * @return array|null
     */
    public function getDmlAdditionalAccessories(): array|null
    {
        return $this->dmlAdditionalAccessories ?? null;
    }

    /**
     * @param string $key
     * @return string
     * @throws \Exception
     */
    public function getDataType(string $key): string
    {
        if (!isset($this->dataTypes[$key])) {
            throw new Exception('This data type is not supported by this driver.');
        }

        return $this->dataTypes[$key];
    }

    /**
     * @param string $key
     * @return string
     * @throws \Exception
     */
    public function getDdlAccessory(string $key): string
    {
        if (!isset($this->ddlAccessories[$key])) {
            throw new Exception('This column accessory is not supported by this driver.');
        }

        return $this->ddlAccessories[$key];
    }

    /**
     * @return string
     */
    public function getDriverName(): string
    {
        return AvailableDbmsDrivers::getDrivers()[strtoupper(
            $this->useUnderscoreInDriverNameWhenSeparating
                ? implode('_', array_filter(preg_split('/(?=[A-Z])/', $this->getCleanDbmsName())))
                : $this->getCleanDbmsName()
        )];
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
        return str_replace([__NAMESPACE__, '/', '\\', 'Driver'], '', get_called_class());
    }
}