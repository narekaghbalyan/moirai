<?php

namespace Moirai\Drivers;

use Exception;
use Moirai\Drivers\Grammars\Grammar;

abstract class Driver
{
    /**
     * @var Grammar
     */
    protected Grammar $grammar;

    /**
     * @var array
     */
    protected array $pitaForColumns;

    /**
     * @var array
     */
    protected array $pitaForStrings;

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
     * @return array|null
     */
    public function getDmlAdditionalAccessories(): array|null
    {
        return $this->dmlAdditionalAccessories ?? null;
    }







    /**
     * @return array
     */
    public function getAllowedForeignKeyActions(): array
    {
        return $this->allowedForeignKeyActions;
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