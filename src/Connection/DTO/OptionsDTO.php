<?php

namespace Moirai\Connection\DTO;

use Moirai\Connection\DTO\Interfaces\OptionsDTOInterface;
use PDO;

class OptionsDTO implements OptionsDTOInterface
{
    /**
     * @var bool|null
     */
    private bool|null $persistent;

    /**
     * @var bool|null
     */
    private bool|null $emulatePrepares;

    /**
     * @var bool|null
     */
    private bool|null $autocommit;

    /**
     * @var int|null
     */
    private int|null $case;

    /**
     * @var int|null
     */
    private int|null $errorMode;

    /**
     * @var int|null
     */
    private int|null $defaultFetchMode;

    /**
     * @var int|null
     */
    private int|null $timeout;

    /**
     * @var int|null
     */
    private int|null $cursor;

    /**
     * @var string|null
     */
    private string|null $statementClass;

    /**
     * OptionsDTO constructor.
     *
     * @param bool|null $persistent
     * @param bool|null $emulatePrepares
     * @param bool|null $autocommit
     * @param int|null $case
     * @param int|null $errorMode
     * @param int|null $defaultFetchMode
     * @param int|null $timeout
     * @param int|null $cursor
     * @param string|null $statementClass
     */
    private function __construct(
        bool|null $persistent,
        bool|null $emulatePrepares,
        bool|null $autocommit,
        int|null $case,
        int|null $errorMode,
        int|null $defaultFetchMode,
        int|null $timeout,
        int|null $cursor,
        string|null $statementClass
    )
    {
        $this->persistent = $persistent;
        $this->emulatePrepares = $emulatePrepares;
        $this->autocommit = $autocommit;
        $this->case = $case;
        $this->errorMode = $errorMode;
        $this->defaultFetchMode = $defaultFetchMode;
        $this->timeout = $timeout;
        $this->cursor = $cursor;
        $this->statementClass = $statementClass;
    }

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
    ): self
    {
        return new self(
            $persistent,
            $emulatePrepares,
            $autocommit,
            $case,
            $errorMode,
            $defaultFetchMode,
            $timeout,
            $cursor,
            $statementClass
        );
    }

    /**
     * @return bool
     */
    public function persistent(): bool
    {
        return $this->persistent ?? false;
    }

    /**
     * @return bool
     */
    public function emulatePrepares(): bool
    {
        return $this->emulatePrepares ?? true;
    }

    /**
     * @return bool
     */
    public function autocommit(): bool
    {
        return $this->autocommit ?? true;
    }

    /**
     * @return int
     */
    public function case(): int
    {
        return $this->case ?? PDO::CASE_NATURAL;
    }

    /**
     * @return int
     */
    public function errorMode(): int
    {
        return $this->errorMode ?? PDO::ERRMODE_SILENT;
    }

    /**
     * @return int
     */
    public function defaultFetchMode(): int
    {
        return $this->defaultFetchMode ?? PDO::FETCH_ASSOC;
    }

    /**
     * @return int|null
     */
    public function timeout(): int|null
    {
        return $this->timeout;
    }

    /**
     * @return int
     */
    public function cursor(): int
    {
        return $this->cursor ?? PDO::CURSOR_FWDONLY;
    }

    /**
     * @return string|null
     */
    public function statementClass(): string|null
    {
        return $this->statementClass;
    }
}
