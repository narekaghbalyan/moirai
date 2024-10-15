<?php

namespace Moirai\Connection;

use Exception;
use Moirai\Connection\DTO\Interfaces\CredentialsDTOInterface;
use Moirai\Connection\DTO\Interfaces\FileConnectionDTOInterface;
use PDO;
use PDOException;

class DBH
{
    /**
     * @var \PDO
     */
    private PDO $dbh;

    /**
     * @var \Moirai\Connection\DTO\Interfaces\CredentialsDTOInterface|\Moirai\Connection\DTO\Interfaces\FileConnectionDTOInterface
     */
    private CredentialsDTOInterface|FileConnectionDTOInterface $dto;

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
