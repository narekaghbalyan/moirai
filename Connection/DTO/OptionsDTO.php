<?php

namespace Moirai\Connection\DTO;

use Moirai\Connection\DTO\Interfaces\OptionsDTOInterface;

class OptionsDTO implements OptionsDTOInterface
{
    /**
     * @var bool|null
     */
    private bool|null $persistent;

    /**
     * CredentialsDTO constructor.
     *
     * @param bool $persistent
     */
    private function __construct(bool|null $persistent)
    {
        $this->persistent = $persistent;
    }

    /**
     * @param bool|null $persistent
     * @return static
     */
    public static function create(bool|null $persistent): self
    {
        return new self($persistent);
    }

    /**
     * @return bool|null
     */
    public function persistent(): bool|null
    {
        return $this->persistent;
    }
}
