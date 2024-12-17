<?php

namespace Moirai\DDL\Migration;

/**
 * @mixin \Moirai\DDL\Blueprint
 */
abstract class Migration implements MigrationInterface
{
    /**
     * Connection key from configs.php
     *
     * @var string
     */
    protected string $connection = 'default';

    /**
     * Table name
     *
     * @var string
     */
    protected string $table;
}