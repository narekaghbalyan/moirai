<?php

namespace Moirai\Connection\DTO\Interfaces;

interface OptionsDTOInterface
{
    /**
     * @param bool $persistent
     * @return static
     */
    public static function create(bool $persistent): self;

    /**
     * @return bool|null
     */
    public function persistent(): bool|null;
}
