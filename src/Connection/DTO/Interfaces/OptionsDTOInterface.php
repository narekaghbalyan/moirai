<?php

namespace Moirai\Connection\DTO\Interfaces;

interface OptionsDTOInterface
{
    /**
     * @param bool|null $persistent
     * @param bool|null $emulatePrepares
     * @param bool|null $autocommit
     * @param int|null $case
     * @param int|null $errorMode
     * @param int|null $defaultFetchMode
     * @param int|null $timeout
     * @param int|null $cursor
     * @param string|null $statementClass
     * @return static
     */
    public static function create(
        bool|null $persistent,
        bool|null $emulatePrepares,
        bool|null $autocommit,
        int|null $case,
        int|null $errorMode,
        int|null $defaultFetchMode,
        int|null $timeout,
        int|null $cursor,
        string|null $statementClass
    ): self;

    /**
     * @return bool
     */
    public function persistent(): bool;

    /**
     * @return bool
     */
    public function emulatePrepares(): bool;

    /**
     * @return bool
     */
    public function autocommit(): bool;

    /**
     * @return int
     */
    public function case(): int;

    /**
     * @return int
     */
    public function errorMode(): int;

    /**
     * @return int
     */
    public function defaultFetchMode(): int;

    /**
     * @return int|null
     */
    public function timeout(): int|null;

    /**
     * @return int
     */
    public function cursor(): int;

    /**
     * @return string|null
     */
    public function statementClass(): string|null;
}
