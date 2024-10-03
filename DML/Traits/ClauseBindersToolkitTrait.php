<?php

namespace Moirai\DML\Traits;

use Exception;
use Moirai\DML\FullTextSearchModifiers;
use Moirai\Drivers\AvailableDbmsDrivers;
use ReflectionClass;

trait ClauseBindersToolkitTrait
{
    /**
     * @param string $bindingName
     * @param string $passedLogicalType
     * @return string
     */
    protected function resolveLogicalType(string $bindingName, string $passedLogicalType): string
    {
        if (!empty($passedLogicalType)) {
            return $passedLogicalType;
        }

         return !empty($this->getBinding($bindingName)) ? 'AND' : '';
    }

    /**
     * @param string|array $subject
     * @return string|array
     */
    protected function wrapColumnInPita(string|array $subject): string|array
    {
        $pitaForColumns = $this->driver->getPitaForColumns();

        return $this->concludeEntities($subject, $pitaForColumns['opening'], $pitaForColumns['closing']);
    }

    /**
     * @param string|array $subject
     * @return string|array
     */
    protected function wrapStringInPita(string|array $subject): string|array
    {
        $pitaForStrings = $this->driver->getPitaForStrings();

        return $this->concludeEntities($subject, $pitaForStrings['opening'], $pitaForStrings['closing']);
    }

    /**
     * @param string|array $subject
     * @return string|array
     */
    protected function concludeSingleQuotes(string|array $subject): string|array
    {
        return $this->concludeEntities($subject, "'");
    }

    /**
     * @param string|array $subject
     * @return string|array
     */
    protected function concludeDoubleQuotes(string|array $subject): string|array
    {
        return $this->concludeEntities($subject, '"');
    }

    /**
     * @param string|array $subject
     * @return string|array
     */
    protected function concludeGraveAccent(string|array $subject): string|array
    {
        return $this->concludeEntities($subject, '`');
    }

    /**
     * @param string|array $subject
     * @return string|array
     */
    protected function concludeBrackets(string|array $subject): string|array
    {
        return $this->concludeEntities($subject, '(', ')');
    }

    /**
     * @param string|array $subject
     * @param string $openSymbol
     * @param string|null $closingSymbol
     * @return string|array
     */
    private function concludeEntities(string|array $subject, string $openSymbol, string $closingSymbol = null): string|array
    {
        $flattenedSubject = [];

        if (is_null($closingSymbol)) {
            $closingSymbol = $openSymbol;
        }

        if (is_string($subject)) {
            return $openSymbol . $subject . $closingSymbol;
        } else {
            array_walk_recursive($subject, function ($item) use (&$flattenedSubject, $openSymbol, $closingSymbol) {
                $flattenedSubject[] = $openSymbol . $item . $closingSymbol;
            });
        }

        return $flattenedSubject;
    }

    private function useAdditionalAccessories(): void
    {
        $additionalAccessories = $this->driver->getAdditionalAccessories();

        if (!empty($additionalAccessories)) {
            foreach ($additionalAccessories as $accessoryName => $accessory) {
                $this->{$accessoryName} = array_merge($this->{$accessoryName}, $accessory);
            }
        }
    }

    /**
     * @param string $column
     * @param bool $forUpdate
     * @return array
     */
    protected function divideSubsequenceFromSequence(string $column, bool $forUpdate = false): array
    {
        $sequence = explode('->', $column);

        $column = $sequence[0];

        unset($sequence[0]);

        if (count($sequence) >= 1) {
            $subsequence = match ($this->getDriverName()) {
                AvailableDbmsDrivers::POSTGRESQL => !$forUpdate
                    ? '->' . implode('->', $this->wrapStringInPita($sequence))
                    : ', ' . $this->wrapStringInPita(
                        '{' . implode(', ', $this->wrapColumnInPita($sequence)) . '}'
                    ),
                default => ', ' . $this->wrapStringInPita(
                        '$.' . implode('.', $this->concludeDoubleQuotes($sequence))
                    )
            };
        } else {
            $subsequence = '';
        }

        return compact('subsequence', 'column');
    }

    /**
     * @param string $direction
     * @return string
     */
    private function supplementDirection(string $direction): string
    {
        if (!in_array(strtolower($direction), ['asc', 'desc'])) {
            $direction = match (strtolower($direction)) {
                'nulls first' => 'ASC ' . $direction,
                'nulls last' => 'DESC ' . $direction
            };
        }

        return $direction;
    }

    /**
     * @param string $bindingName
     * @param string $whereLogicalType
     * @param callable $callback
     */
    protected function runCallback(string $bindingName, string $whereLogicalType, callable $callback): void
    {
        $this->bind($bindingName, [$whereLogicalType]);

        $this->bind($bindingName, ['(']);

        $callback($this);

        $this->bind($bindingName, [')']);
    }

    /**
     * @param string $bindingName
     * @param callable $callback
     */
    protected function runCallbackForVirginInstance(string $bindingName, callable $callback): void
    {
        $this->bind($bindingName, ['(']);

        $virginInstance = new $this();

        $callback($virginInstance);

        $this->bind($bindingName, [$virginInstance]);

        $this->bind($bindingName, [')']);
    }

    /**
     * @param string|int|float $value
     * @return string|int|float
     */
    protected function solveValueWrappingInPita(string|int|float $value): string|int|float
    {
        return !is_string($value) ? $value : $this->wrapStringInPita($value);
    }

    /**
     * @param string|int|float|array $suspect
     * @param array $dataFromWhichToCheck
     * @return bool
     */
    protected function checkMatching(string|int|float|array $suspect, array $dataFromWhichToCheck): bool
    {
        if (is_array($suspect)) {
            foreach ($suspect as $item) {
                if (!in_array($item, $dataFromWhichToCheck)) {
                    return false;
                }
            }

            return true;
        }

        return in_array($suspect, $dataFromWhichToCheck);
    }

    /**
     * @param array $array
     * @return bool
     */
    protected function isAssociative(array $array): bool
    {
        return array_keys($array) !== range(0, count($array) - 1);
    }

    /**
     * @param mixed $suspect
     * @throws \Exception
     */
    protected function throwExceptionIfMisplacedArray(mixed $suspect): void
    {
        if (is_array($suspect)) {
            throw new Exception('Invalid value specified. The value cannot be an array.');
        }
    }

    /**
     * @param array $array
     * @param string|null $message
     * @throws \Exception
     */
    protected function throwExceptionIfArrayAssociative(array $array, string|null $message = null): void
    {
        if ($this->isAssociative($array)) {
            throw new Exception(
                is_null($message) ? 'Array cannot be associative.' : $message
            );
        }
    }

    /**
     * @param array $array
     * @param string|null $message
     * @throws \Exception
     */
    protected function throwExceptionIfArrayIsNotAssociative(array $array, string|null $message = null): void
    {
        if (!$this->isAssociative($array)) {
            throw new Exception(
                is_null($message) ? 'Array must be associative.' : $message
            );
        }
    }

    /**
     * @param string $operator
     * @throws \Exception
     */
    protected function throwExceptionIfOperatorIsInvalid(string $operator): void
    {
        if (!$this->checkMatching($operator, $this->getOperators())) {
            throw new Exception(
                '"' . $operator . '" is not a SQL operator.'
            );
        }
    }

    /**
     * @param string|array $direction
     * @throws \Exception
     */
    protected function throwExceptionIfDirectionIsInvalid(string|array $direction): void
    {
        if (!$this->checkMatching($direction, $this->orderDirections)) {
            throw new Exception(
                'Direction values for "order by" expression are not valid.'
            );
        }
    }

    /**
     * @param string $modifier
     * @throws \Exception
     */
    protected function throwExceptionIfFtsModifierIsInvalid(string $modifier): void
    {
        $reflectionClass = new ReflectionClass(FullTextSearchModifiers::class);

        if (!$this->checkMatching($modifier, $reflectionClass->getConstants())) {
            throw new Exception(
                '"' . $modifier . '" is not a full text search modifier.'
            );
        }
    }

    /**
     * @param mixed $suspect
     * @throws \Exception
     */
    protected function throwExceptionIfArgumentNotNumeric(mixed $suspect): void
    {
        if (!is_numeric($suspect)) {
            throw new Exception('"' . $suspect . '" is not a number or a numeric string.');
        }
    }

    /**
     * @throws \Exception
     */
    protected function throwExceptionIfDriverNotSupportFunction(): void
    {
        throw new Exception('DriverInterface ' . $this->getDriverName() . ' does not support this function.');
    }
}
