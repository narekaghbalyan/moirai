<?php

namespace Moirai\Connection;

use Moirai\Drivers\AvailableDbmsDrivers;

class CredentialsManager
{
    /**
     * @throws Exception
     */
    private function apply()
    {
        $dbmsDriver = $this->getConfigValue('dbms_driver') ?: AvailableDbmsDrivers::MYSQL;

        if (!in_array($dbmsDriver, $this->localAvailableDbmsDrivers)) {
            throw new Exception(
                'Database management system driver "'
                . $dbmsDriver
                . '" is not supported. Only the following drivers are supported: "'
                . implode('", "', $this->localAvailableDbmsDrivers)
                . '".'
            );
        }

        if (!in_array(
            $this->localAndPdoDbmsDriversConformity[$dbmsDriver],
            $this->pdoAvailableDbmsDrivers
        )) {
            throw new Exception(
                'Database management system driver "'
                . $this->configs['connections'][$this->connectionKey]['db_driver']
                . '" is not supported by PDO. Only the following drivers are supported: "'
                . implode('", "', $this->pdoAvailableDbmsDrivers)
                . '". The driver module may not be installed.'
            );
        }

        if ($dbmsDriver !== AvailableDbmsDrivers::SQLITE) {
            $this->credentials = [
                'host' => $this->getConfigValue('host', true),
                'port' => $this->getConfigValue('port') ?? 3306,
                'database' => $this->getConfigValue('database', true),
                'username' => $this->getConfigValue('username') ?? '',
                'password' => $this->getConfigValue('password') ?? '',
                'dbms_driver' => $dbmsDriver
            ];

            return;
        }

        $this->credentials = [
            'file_path' => $this->getConfigValue('host', true)
        ];
    }




}
