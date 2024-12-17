<?php

namespace Moirai\DDL;

use Exception;
use Moirai\Drivers\DriverInterface;
use Moirai\Drivers\PostgreSqlDriver;
use Moirai\Drivers\OracleDriver;
use Moirai\DDL\Constraints\DefinedColumnConstraints;
use Moirai\DDL\Constraints\ColumnConstraints;
use Moirai\DDL\Constraints\TableConstraints;

class DDL
{
    /**
     * @var \Moirai\Drivers\DriverInterface
     */
    protected DriverInterface $driver;

    /**
     * @var string
     */
    protected string $table;

    /**
     * @var string
     */
    protected string $action;

    /**
     * @var array
     */
    public array $columnsDefinitionsBindings = [];

    /**
     * @var array
     */
    private array $tableConstraintsBindings = [];

    /**
     * @var array
     */
    private array $alterActionsBindings = [];

    /**
     * @var array
     */
    protected array $chainedStatements = [];

    /**
     * @var array
     */
    public array $modify = [];

    /**
     * @return array
     * @throws \Exception
     */
    public function getColumnsDefinitions(): array
    {
        return $this->sewColumnsBindings();
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getTableConstraintsDefinitions(): array
    {
        return $this->sewTableConstraintsBindings();
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getAlterActionsDefinitions(): array
    {
        return $this->sewAlterActionsBindings();
    }

    /**
     * @return array
     */
    public function getChainedStatements(): array
    {
        return $this->chainedStatements;
    }

    /**
     * @param string $column
     * @param int $dataType
     * @param array|null $parameters
     * @param array|null $constraints
     * @return \Moirai\DDL\Constraints\DefinedColumnConstraints
     */
    protected function bindColumnDefinition(
        string $column,
        int $dataType,
        array|null $parameters = null,
        array|null $constraints = null
    ): DefinedColumnConstraints
    {
        $this->columnsDefinitionsBindings[$column] = [
            'data_type' => $dataType,
            'parameters' => $parameters,
            'constraints' => $constraints
        ];

        return new DefinedColumnConstraints($column, $this);
    }

    /**
     * @param int $type
     * @param array $parameters
     * @throws \Exception
     */
    protected function bindTableConstraint(int $type, array $parameters): void
    {
        if ($type === TableConstraints::PRIMARY_KEY) {
            if (!empty(array_filter($this->tableConstraintsBindings, function ($tableConstraint) {
                return $tableConstraint['type'] === TableConstraints::PRIMARY_KEY;
            }))) {
                throw new Exception('Primary key already exists in table "' . $this->table . '".');
            }
        }

        $this->tableConstraintsBindings[] = compact('type', 'parameters');
    }

    /**
     * @param int $action
     * @param array $parameters
     */
    protected function bindAlterAction(int $action, array $parameters = []): void
    {
        $this->alterActionsBindings = compact('action', 'parameters');
    }

    /**
     * @param string $type
     * @param array $parameters
     * @throws \Exception
     */
    protected function bindIndex(string $type, array $parameters): void
    {
        $this->chainedStatements[] = str_replace(
            array_map(fn($key) => '{' . $key . '}', array_keys($parameters)),
            array_values($parameters),
            $this->driver->getLexis()->getIndex($type)
        );
    }

    /**
     * @return array
     * @throws \Exception
     */
    private function sewColumnsBindings(): array
    {
        $sewedColumns = [];

        foreach ($this->columnsDefinitionsBindings as $column => $options) {
            $definitionSignature = $this->driver->getLexis()->getDataType($options['data_type']);

            foreach ($options['parameters'] as $parameterKey => $parameterValue) {
                $parameterKey = '{' . $parameterKey . '}';

                if (!is_null($parameterValue)) {
                    if (!str_contains($definitionSignature, $parameterKey)) {
                        throw new Exception(
                            'DBMS driver "'
                            . $this->driver->getName()
                            . '" do not support parameters for data type "'
                            . $definitionSignature . '".'
                        );
                    }
                } else {
                    $parameterKey = '({' . $parameterKey . '})';
                    $parameterValue = '';
                }

                $definitionSignature = str_replace(
                    $parameterKey,
                    $parameterValue,
                    $definitionSignature
                );
            }

            foreach ($options['constraints'] as $constraintKey => $constraintParameters) {
                $constraintDefinitionSignature = $this->driver->getLexis()->getColumnConstraint($constraintKey);

                if ($constraintKey === ColumnConstraints::COMMENT
                    && in_array($this->driver::class, [PostgreSqlDriver::class, OracleDriver::class])) {
                    $constraintParameters['table'] = $this->table;
                    $constraintParameters['column'] = $column;
                }

                foreach ($constraintParameters as $constraintParameterKey => $constraintParameterValue) {
                    $constraintDefinitionSignature = str_replace(
                        '{' . $constraintParameterKey . '}',
                        $constraintParameterValue,
                        $constraintDefinitionSignature
                    );
                }

                if ($constraintKey === ColumnConstraints::COMMENT
                    && in_array($this->driver::class, [PostgreSqlDriver::class, OracleDriver::class])) {
                    $this->chainedStatements[] = $constraintDefinitionSignature;
                } else {
                    $definitionSignature .= ' ' . $constraintDefinitionSignature;
                }
            }

            $sewedColumns[$column] = $column . ' ' . $definitionSignature;
        }

        return $sewedColumns;
    }

    /**
     * @return array
     * @throws \Exception
     */
    private function sewTableConstraintsBindings(): array
    {
        $sewedTableConstraints = [];

        $tableConstraintParameterKeyToPlaceholder = [
            'name' => 'CONSTRAINT {name}',
            'on_delete_action' => 'ON DELETE {on_delete_action}',
            'on_update_action' => 'ON UPDATE {on_update_action}'
        ];

        foreach ($this->tableConstraintsBindings as $tableConstraint) {
            $definitionSignature = $this->driver->getLexis()->getTableConstraint($tableConstraint['type']);

            foreach ($tableConstraint['parameters'] as $parameterKey => $parameterValue) {
                if (is_null($parameterValue)
                    && isset($tableConstraintParameterKeyToPlaceholder[$parameterKey])) {
                    $definitionSignature = str_replace(
                        $tableConstraintParameterKeyToPlaceholder[$parameterKey],
                        '',
                        $definitionSignature
                    );
                }

                if (in_array($parameterKey, ['on_delete_action', 'on_update_action'])
                    && !in_array($parameterValue, $this->driver->getAllowedForeignKeyActions())) {
                    throw new Exception(
                        'DBMS driver "'
                        . $this->driver->getName()
                        . '" does not support "'
                        . $parameterValue
                        . '" action as foreign key action.'
                    );
                }

                $definitionSignature = str_replace(
                    '{' . $parameterKey . '}',
                    $parameterValue,
                    $definitionSignature
                );
            }

            $sewedTableConstraints[] = $definitionSignature;
        }

        return $sewedTableConstraints;
    }

    /**
     * @return array
     * @throws \Exception
     */
    private function sewAlterActionsBindings(): array
    {
        $sewedAlterActions = [];

        foreach ($this->alterActionsBindings as $alterAction) {
            $sewedAlterActions[] = strtr(
                $this->driver->getLexis()->getTableConstraint($alterAction['action']),
                array_map(
                    fn($key) => '{' . $key . '}',
                    array_keys($alterAction['parameters'])
                )
            );
        }

        return $sewedAlterActions;
    }
}