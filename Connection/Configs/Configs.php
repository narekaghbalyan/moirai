<?php

namespace Moirai\Connection;

use Exception;

class Configs
{
    /**
     * @var array
     */
    private array $configs;

    /**
     * @var string
     */
    private string $connectionKey;

    /**
     * Configs constructor.
     *
     * @param string $configsFilePath
     * @param string $connectionKey
     * @throws \Exception
     */
    public function __construct(string $configsFilePath, string $connectionKey)
    {
        $this->configs = include($configsFilePath);
        $this->connectionKey = $connectionKey;

        ConfigsValidator::validate($this->configs, $this->connectionKey);
    }

    /**
     * @param string $key
     * @param bool $required
     * @return mixed
     * @throws \Exception
     */
    public function getConfigValue(string $key, $required = false): mixed
    {
        if ($required && empty($this->configs['connections'][$this->connectionKey][$key])) {
            throw new Exception(
                'The connection argument "'
                . $key
                . '"  is required, but value is empty or not specified. It must be specified in the configs file under the key "'
                . $key
                . '" in the connection "'
                . $this->connectionKey
                . '". If it should not have a value then you can specify it with an empty value but you must be 
                    sure to declare it in the above location.'
            );
        }

        return $this->configs['connections'][$this->connectionKey][$key] ?? null;
    }
}
