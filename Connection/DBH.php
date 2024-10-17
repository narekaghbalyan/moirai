<?php

namespace Moirai\Connection;

use Exception;
use Moirai\Connection\DTO\Interfaces\CredentialsDTOInterface;
use Moirai\Connection\DTO\Interfaces\FileConnectionDTOInterface;
use Moirai\Connection\DTO\Interfaces\OptionsDTOInterface;
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
     * @var \Moirai\Connection\DTO\Interfaces\OptionsDTOInterface
     */
    private OptionsDTOInterface $optionsDTO;

    /**
     * @var \PDO
     */
    private PDO $dbh;

    /**
     * DBH constructor.
     *
     * @param \Moirai\Connection\DTO\Interfaces\CredentialsDTOInterface|\Moirai\Connection\DTO\Interfaces\FileConnectionDTOInterface $dto
     * @param \Moirai\Connection\DTO\Interfaces\OptionsDTOInterface $optionsDTO
     * @throws \Exception
     */
    public function __construct(CredentialsDTOInterface|FileConnectionDTOInterface $dto, OptionsDTOInterface $optionsDTO)
    {
        $this->dto = $dto;
        $this->optionsDTO = $optionsDTO;

        $this->initialize();
    }

    /**
     * @throws Exception
     */
    private function initialize(): void
    {
        $this->checkDbmsDriver();

        $options = [
            PDO::ATTR_EMULATE_PREPARES => true,
            PDO::ATTR_PERSISTENT => $this->optionsDTO->persistent() ?? true
        ];

        try {
            if ($this->dto instanceof CredentialsDTOInterface) {
                $this->dbh = new PDO(
                    $this->sculptDsn(),
                    $this->dto->username(),
                    $this->dto->password(),
                    $options
                );
            } else {
                $this->dbh = new PDO(
                    $this->dto->dbmsDriver() . ':' . $this->dto->filePath(),
                    null,
                    null,
                    $options
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
    private function checkDbmsDriver(): void
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
