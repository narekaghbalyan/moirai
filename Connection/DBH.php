<?php

namespace Moirai\Connection;

use Exception;
use Moirai\Connection\DTO\Interfaces\CredentialsDTOInterface;
use Moirai\Connection\DTO\Interfaces\FileConnectionDTOInterface;
use Moirai\Drivers\AvailableDbmsDrivers;
use PDO;
use PDOException;

class DBH
{
    /**
     * @var \Moirai\Connection\DTO\Interfaces\CredentialsDTOInterface|\Moirai\Connection\DTO\Interfaces\FileConnectionDTOInterface
     */
    private CredentialsDTOInterface|FileConnectionDTOInterface $dto;

    /**
     * @var \PDO
     */
    private PDO $dbh;

    /**
     * @var array|bool[]
     */
    private array $options = [
        PDO::ATTR_EMULATE_PREPARES => true,
        PDO::ATTR_PERSISTENT => true
    ];

    /**
     * DBH constructor.
     * @param \Moirai\Connection\DTO\Interfaces\CredentialsDTOInterface|\Moirai\Connection\DTO\Interfaces\FileConnectionDTOInterface $dto
     * @throws \Exception
     */
    public function __construct(CredentialsDTOInterface|FileConnectionDTOInterface $dto)
    {
        $this->dto = $dto;

        $this->initialize();
    }

    /**
     * @throws Exception
     */
    private function initialize(): void
    {
        $this->checkDbmsDriver();

        try {
            if ($this->dto instanceof CredentialsDTOInterface) {
                $this->dbh = new PDO(
                    $this->sculptDsn(),
                    $this->dto->username(),
                    $this->dto->password(),
                    $this->options
                );
            } else {
                $this->dbh = new PDO(
                    $this->dto->dbmsDriver() . ':' . $this->dto->filePath(),
                    null,
                    null,
                    $this->options
                );
            }

            $this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // ?
        } catch (PDOException $exception) {
            throw new Exception($exception->getMessage());
        }
    }

    /**
     * @throws \Exception
     */
    private function checkDbmsDriver()
    {
        $localAvailableDbmsDrivers = AvailableDbmsDrivers::getDrivers();

        if (!in_array($this->dto->dbmsDriver(), $localAvailableDbmsDrivers)) {
            throw new Exception(
                'Database management system driver "'
                . $this->dto->dbmsDriver()
                . '" is not supported. Only the following drivers are supported: "'
                . implode('", "', $localAvailableDbmsDrivers)
                . '".'
            );
        }

        $localAndPdoDbmsDriversConformity = [
            AvailableDbmsDrivers::MYSQL => 'mysql',
            AvailableDbmsDrivers::POSTGRESQL => 'pgsql',
            AvailableDbmsDrivers::SQLITE => 'sqlite',
            AvailableDbmsDrivers::MS_SQL_SERVER => 'sqlsrv',
            AvailableDbmsDrivers::MARIADB => 'mysql',
            AvailableDbmsDrivers::ORACLE => 'oci'
        ];

        $pdoAvailableDbmsDrivers = PDO::getAvailableDrivers();

        if (!in_array(
            $localAndPdoDbmsDriversConformity[$this->dto->dbmsDriver()],
            $pdoAvailableDbmsDrivers
        )) {
            throw new Exception(
                'Database management system driver "'
                . $this->dto->dbmsDriver()
                . '" is not supported by PDO. Only the following drivers are supported: "'
                . implode('", "', $pdoAvailableDbmsDrivers)
                . '". The driver module may not be installed.'
            );
        }
    }

    /**
     * @return string
     */
    private function sculptDsn(): string
    {
        return $this->dto->dbmsDriver()
            . ':host=' . $this->dto->host()
            . ';port=' . $this->dto->port()
            . ';dbname=' . $this->dto->database();
    }
}
