<?php

namespace Moirai\Connection\Providers;

use Moirai\Connection\Connection;
use Exception;
use PDO as PHPPDO;
use PDOException;

class PDO extends Connection
{
    /**
     *
     */
    public function initialize(): void
    {
        try {
            $this->dbh = new PHPPDO(
                $this->sculptDsn(),
                $this->credentials['username']['value'],
                $this->credentials['password']['value'],
                [
                    PHPPDO::ATTR_PERSISTENT => true
                ]
            );

            $this->dbh->setAttribute(PHPPDO::ATTR_ERRMODE, PHPPDO::ERRMODE_EXCEPTION);
        } catch (PDOException $exception) {
            // attempt to retry the connection after some timeout for example
        }
    }

    private function sculptDsn(): string
    {
        return $this->credentials['driver']['value']
            . ':host='
            . $this->credentials['host']['value']
            . ';port='
            . $this->credentials['port']['value']
            . ';dbname='
            . $this->credentials['database']['value'];
    }
}
