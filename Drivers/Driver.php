<?php

namespace Moirai\Drivers;

use Moirai\Drivers\Grammars\Grammar;
use Moirai\Drivers\Lexises\Lexis;

abstract class Driver
{
    /**
     * @var string
     */
    protected string $name;

    /**
     * @var \Moirai\Drivers\Grammars\Grammar
     */
    protected Grammar $grammar;

    /**
     * @var \Moirai\Drivers\Lexises\Lexis
     */
    protected Lexis $lexis;

    /**
     * @var array
     */
    protected array $allowedForeignKeyActions;

    /**
     * @var array|null
     */
    protected array|null $additionalAccessories = null;

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return \Moirai\Drivers\Grammars\Grammar
     */
    public function getGrammar(): Grammar
    {
        return $this->grammar;
    }

    /**
     * @return \Moirai\Drivers\Lexises\Lexis
     */
    public function getLexis(): Lexis
    {
        return $this->lexis;
    }

    /**
     * @return array
     */
    public function getAllowedForeignKeyActions(): array
    {
        return $this->allowedForeignKeyActions;
    }

    /**
     * @return array|null
     */
    public function getAdditionalAccessories(): array|null
    {
        return $this->additionalAccessories;
    }
}