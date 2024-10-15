<?php

namespace Moirai\Connection;

use Exception;

class ConfigsValidator
{
    /**
     * @param array $configs
     * @param string $connectionKey
     * @throws Exception
     */
    public static function validate(array $configs, string $connectionKey): void
    {
        if (empty($configs['connections'])) {
            throw new Exception('Connection(s) are empty in config file.');
        }

        if (empty($configs['connections'][$connectionKey])) {
            throw new Exception(
                'Could not find a connection credentials with connection key "'
                . $connectionKey
                . '" in config file.'
            );
        }
    }
}
