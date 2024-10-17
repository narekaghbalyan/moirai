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
     * Configs constructor.
     *
     * @param string $configsFilePath
     * @throws \Exception
     */
    public function __construct(string $configsFilePath)
    {
        $this->configs = include($configsFilePath);
    }

    /**
     * When you need to pass nested keys, you need to separate them with a dot.
     *
     * @param string $keyInDotNotation
     * @param bool $required
     * @return mixed
     * @throws \Exception
     */
    public function getValue(string $keyInDotNotation, $required = false): mixed
    {
        $keys = explode('.', $keyInDotNotation);

        $value = $this->configs;

        foreach ($keys as $key) {
            if (isset($value[$key])) {
                $value = $value[$key];
            } else {
                $value = null;

                break;
            }
        }

        if ($required && empty($value)) {
            throw new Exception(
                'The connection argument "'
                . end($keys)
                . '"  is required, but value is empty or not specified. It must be specified in the configs file under the key(s) "'
                . implode('" => "', $keys)
                . '".'
            );
        }

        return $value ?? null;
    }
}
