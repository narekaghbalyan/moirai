<?php

namespace Moirai\Drivers;

use Moirai\Drivers\Grammars\Grammar;
use Moirai\Drivers\Lexises\Lexis;

interface DriverInterface
{
    /**
     * @return string
     */
    public function getName(): string;

    /**
     * @return \Moirai\Drivers\Grammars\Grammar
     */
    public function getGrammar(): Grammar;

    /**
     * @return \Moirai\Drivers\Lexises\Lexis
     */
    public function getLexis(): Lexis;

    /**
     * @return array
     */
    public function getAllowedForeignKeyActions(): array;

    /**
     * @return array|null
     */
    public function getAdditionalAccessories(): array|null;
}