<?php

namespace Moirai\CLI\Traits;

trait CLIToolkit
{
    /**
     * @var string
     */
    protected static string $prefixForSuccessMessages = '[+]';

    /**
     * @var string
     */
    protected static string $prefixForFailedMessages = '[-]';
}
