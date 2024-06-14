<?php

namespace Moirai\DML;

use Moirai\Drivers\AvailableDbmsDrivers;
use ReflectionClass;
use Exception;

class QueryBuilderRepresentativeSpokesman extends QueryBuilder
{
    /**
     * --------------------------------------------------------------------------
     * | Clause to select from database table.                                  |
     * | ------------------------------ Use cases ------------------------------|
     * | select() - selects all columns from a table.                           |
     * |                                                                        |
     * | -- The below variations select the listed columns from the table --    |
     * | | select('column1', 'column2', ..., 'columnN')                    |    |
     * | | select(['column1', 'column2', ..., 'columnN'])                  |    |
     * | | select(['column1', 'column2'], ['column3', ..., 'columnN'])     |    |
     * | -------------------------------------------------------------------    |
     * --------------------------------------------------------------------------
     * @param string|mixed ...$columns
     * @return $this
     */
    public function select(array|string ...$columns): self
    {
        $this->selectClauseBinder(false, $columns);

        return $this;
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause for select different (unique) values in columns.                |
     * | ------------------------------ Use cases ------------------------------|
     * | distinct() - selects only different (unique) values from all columns.  |
     * |                                                                        |
     * | -- The below variations select the listed columns from the table --    |
     * | | distinct('column1', 'column2', ..., 'columnN')                  |    |
     * | | distinct(['column1', 'column2', ..., 'columnN'])                |    |
     * | | distinct(['column1', 'column2'], ['column3', ..., 'columnN'])   |    |
     * | -------------------------------------------------------------------    |
     * | ---------------------------------------------------------------------- |
     * | "distinct" works the same as "select" with the difference that.        |
     * | "select" selects all values of a column while "distinct" only selects  |
     * | the unique values of a column.                                         |
     * --------------------------------------------------------------------------
     * @param string|mixed ...$columns
     * @return $this
     */
    public function distinct(array|string ...$columns): self
    {
        $this->selectClauseBinder(true, $columns);

        return $this;
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to select from database table.                                  |
     * | ------------------------------ Use cases ------------------------------|
     * | getColumn() - selects all columns from a table.                        |
     * |                                                                        |
     * | -- The below variations select the listed columns from the table --    |
     * | | getColumn('column1', 'column2', ..., 'columnN')                 |    |
     * | | getColumn(['column1', 'column2', ..., 'columnN'])               |    |
     * | | getColumn(['column1', 'column2'], ['column3', ..., 'columnN'])  |    |
     * | -------------------------------------------------------------------    |
     * | ---------------------------------------------------------------------- |
     * | Same as "select".                                                      |
     * --------------------------------------------------------------------------
     * @param string|mixed ...$columns
     * @return $this
     */
    public function getColumn(array|string ...$columns): self
    {
        $this->selectClauseBinder(false, $columns);

        return $this;
    }

    // TODO [implement]
    /**
     * --------------------------------------------------------------------------
     * | Clause for fragmentary processing of many records from a table.        |
     * | ------------------------------ Use cases ------------------------------|
     * | chunk(100, function () {                                               |
     * |     // Process the records...                                          |
     * | }) - selects all columns from a table                                  |
     * --------------------------------------------------------------------------
     * @param int|string $count
     * @param callable $callback
     * @return bool
     * @throws \Exception
     */
    public function chunk(int|string $count, callable $callback): bool
    {
        return $this->chunkClauseBinder($count, $callback);
    }

    /**
     * --------------------------------------------------------------------------
     * | In database management, an aggregate function or aggregation function  |
     * | is a function where multiple values are processed together to form a   |
     * | single summary statistic.                                              |
     * --------------------------------------------------------------------------
     */

    /**
     * --------------------------------------------------------------------------
     * | An aggregate function that returns the maximum value from a specific   |
     * | column.                                                                |
     * | ------------------------------ Use cases ----------------------------- |
     * | max('column') - returns the maximum value of column "column".          |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return $this
     */
    public function max(string $column): self
    {
        $this->aggregateFunctionsClauseBinder(__FUNCTION__, $column);

        return $this;
    }

    /**
     * --------------------------------------------------------------------------
     * | An aggregate function that returns the maximum value from a specific   |
     * | column.                                                                |
     * | ------------------------------ Use cases ----------------------------- |
     * | maxDistinct('column') - returns the maximum value of column "column".  |
     * | ---------------------------------------------------------------------- |
     * | Since the operation of comparing and finding the maximum value always  |
     * | occurs using different values since SQL internally converts            |
     * | max('column') to max('column') using the DISTINCT keyword. That is,    |
     * | even when using "max" functions, SQL will convert the expression to    |
     * | "maxDistinct". This means that with or without the word distinct,      |
     * | the max() function returns the maximum value of the distinct values.   |
     * | This means that DISTINCT has no effect on the max() function.          |
     * | ---------------------------------------------------------------------- |
     * | The same as "max".                                                     |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return $this
     */
    public function maxDistinct(string $column): self
    {
        $this->aggregateFunctionsClauseBinder('max', $column, true);

        return $this;
    }

    /**
     * --------------------------------------------------------------------------
     * | An aggregate function that returns the minimum value from a specific   |
     * | column.                                                                |
     * | ------------------------------ Use cases ----------------------------- |
     * | min('column') - returns the minimum value of column "column".          |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return $this
     */
    public function min(string $column): self
    {
        $this->aggregateFunctionsClauseBinder(__FUNCTION__, $column);

        return $this;
    }

    /**
     * --------------------------------------------------------------------------
     * | An aggregate function that returns the minimum value from a specific   |
     * | column.                                                                |
     * | ------------------------------ Use cases ----------------------------- |
     * | min('column') - returns the minimum value of column "column".          |
     * | ---------------------------------------------------------------------- |
     * | Since the operation of comparing and finding the minimum value always  |
     * | occurs using different values since SQL internally converts            |
     * | min('column') to min('column') using the DISTINCT keyword. That is,    |
     * | even when using "min" functions, SQL will convert the expression to    |
     * | "minDistinct". This means that with or without the word distinct,      |
     * | the min() function returns the minimum value of the distinct values.   |
     * | This means that DISTINCT has no effect on the min() function.          |
     * | ---------------------------------------------------------------------- |
     * | The same as "min".                                                     |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return $this
     */
    public function minDistinct(string $column): self
    {
        $this->aggregateFunctionsClauseBinder('min', $column, true);

        return $this;
    }

    /**
     * --------------------------------------------------------------------------
     * | An aggregate function that calculates and returns the sum of a set of  |
     * | values for a specific column.                                          |
     * | ------------------------------ Use cases ----------------------------- |
     * | sum('column') - calculates and returns the sum of a set of values in   |
     * | column `column`.                                                       |
     * | ---------------------------------------------------------------------- |
     * | If you use the "sum" function in a select statement that does not      |
     * | return any rows, the "sum" function returns null rather than zero.     |
     * | That is, if the set of input numbers is empty or all values in the set |
     * | are null, the "sum" function returns null.                             |
     * | Null values are ignored.                                               |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return $this
     */
    public function sum(string $column): self
    {
        $this->aggregateFunctionsClauseBinder(__FUNCTION__, $column);

        return $this;
    }

    /**
     * --------------------------------------------------------------------------
     * | An aggregate function that calculates and returns the sum of unique    |
     * | value for a specific column.                                           |
     * | ------------------------------ Use cases ----------------------------- |
     * | sumDistinct('column') - calculates and returns the sum of the set of   |
     * | unique values in the column "column".                                  |
     * | ---------------------------------------------------------------------- |
     * | If you use the "sumDistinct" function in a select statement that does  |
     * | not return any rows, the "sumDistinct" function returns null rather    |
     * | than zero. That is, if the set of input numbers is empty or all values |
     * | in the set are null, the "sumDistinct" function returns null.          |
     * | Null values are ignored.                                               |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return $this
     */
    public function sumDistinct(string $column): self
    {
        $this->aggregateFunctionsClauseBinder('sum', $column, true);

        return $this;
    }

    /**
     * --------------------------------------------------------------------------
     * | An aggregate function that calculates and returns the average          |
     * | (arithmetic mean) of a specified numeric column.                       |
     * | ------------------------------ Use cases ----------------------------- |
     * | avg('column') - calculates and returns the average (arithmetic mean)
     * | of the column "column".                                                |
     * | ---------------------------------------------------------------------- |
     * | If you use the "avg" function in a select statement that does not      |
     * | return any rows, the "avg" function returns null rather than zero.     |
     * | That is, if the set of input numbers is empty or all values in the set |
     * | are zero, the "avg" function returns null. If the sum exceeds the      |
     * | maximum value for the return value data type, "avg" will return an     |
     * | error.                                                                 |
     * | Null values are ignored.                                               |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return $this
     */
    public function avg(string $column): self
    {
        $this->aggregateFunctionsClauseBinder(__FUNCTION__, $column);

        return $this;
    }

    /**
     * --------------------------------------------------------------------------
     * | An aggregate function that calculates and returns the average          |
     * | (arithmetic mean) of the different (unique) values of a specified      |
     * | numeric column.                                                        |
     * | ------------------------------ Use cases ----------------------------- |
     * | avgDistinct('column') - calculates and returns the average             |
     * | (arithmetic mean) of the different (unique) values of column "column". |                                              |
     * | ---------------------------------------------------------------------- |
     * | If you use the "avgDistinct" function in a select statement that does  |
     * | not return any rows, the "avgDistinct" function returns null rather    |
     * | than zero. That is, if the set of input numbers is empty or all values |
     * | in the set are zero, the "avgDistinct" function returns null. If the   |
     * | sum exceeds the maximum value for the return value data type,          |
     * | "avgDistinct" will return an error.                                    |
     * | Null values are ignored.                                               |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return $this
     */
    public function avgDistinct(string $column): self
    {
        $this->aggregateFunctionsClauseBinder('avg', $column, true);

        return $this;
    }

    /**
     * --------------------------------------------------------------------------
     * | An aggregate function that returns the number of records (rows) in a   |
     * | column.                                                                |
     * | ------------------------------ Use cases ----------------------------- |
     * | count() - calculates and returns the number of records (rows) from all |
     * | columns.                                                               |
     * |                                                                        |
     * | count('column') - calculates and returns the number of records (rows)  |
     * | of column "column".                                                    |
     * | ---------------------------------------------------------------------- |
     * | Null values are ignored.                                               |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return $this
     */
    public function count(string $column = '*'): self
    {
        $this->aggregateFunctionsClauseBinder(__FUNCTION__, $column);

        return $this;
    }

    /**
     * --------------------------------------------------------------------------
     * | An aggregate function that returns the number of distinct (unique)     |
     * | records (rows) in a column.                                            |
     * | ------------------------------ Use cases ----------------------------- |
     * | countDistinct() - calculates and returns the number of distinct        |
     * | (unique) records (rows) from all columns.                              |
     * |                                                                        |
     * | countDistinct('column') - calculates and returns the number of         |
     * | distinct (unique) records (rows) of column "column".                   |                                                   |
     * | ---------------------------------------------------------------------- |
     * | Null values are ignored.                                               |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return $this
     */
    public function countDistinct(string $column = '*'): self
    {
        $this->aggregateFunctionsClauseBinder('count', $column, true);

        return $this;
    }

    /**
     * --------------------------------------------------------------------------
     * | An aggregate function that performs a bitwise AND operation on all     |
     * | input values.                                                          |
     * | ------------------------------ Use cases ----------------------------- |
     * | bitAnd('column') - performs a bitwise AND operation on all values in   |
     * | column "column".                                                       |
     * | ---------------------------------------------------------------------- |
     * | The bitAnd function works by performing a bitwise AND operation on     |
     * | each pair of corresponding bits in the binary representation of the    |
     * | numbers. The result is a new binary number with a 1 in each position.  |
     * | That bitAnd can be used with any integer data type.                    |
     * | Null values are ignored.                                               |
     * --------------------------------------------------------------------------
     * @param string|int $column
     * @return $this
     * @throws \Exception
     */
    public function bitAnd(string|int $column): self
    {
        $this->bitAggregateFunctionClauseBinder('BIT_AND', $column);

        return $this;
    }

    /**
     * --------------------------------------------------------------------------
     * | An aggregate function that performs a bitwise OR operation on all      |
     * | input values.                                                          |
     * | ------------------------------ Use cases ----------------------------- |
     * | bitOr('column') - performs a bitwise OR operation on all values in     |
     * | column "column".                                                       |
     * | ---------------------------------------------------------------------- |
     * | It converts all decimal values to binary values and then performs a    |
     * | bitwise OR operation on those binary values.                           |
     * | Null values are ignored.                                               |
     * --------------------------------------------------------------------------
     * @param string|int $column
     * @return $this
     * @throws \Exception
     */
    public function bitOr(string|int $column): self
    {
        $this->bitAggregateFunctionClauseBinder('BIT_OR', $column);

        return $this;
    }

    /**
     * --------------------------------------------------------------------------
     * | An aggregate function that performs a bitwise COR operation on all     |
     * | input values.                                                          |
     * | ------------------------------ Use cases ----------------------------- |
     * | bitXor('column') - performs a bitwise XOR operation on all values in   |
     * | column "column".                                                       |
     * | ---------------------------------------------------------------------- |
     * | It converts all decimal values to binary values and then performs a    |
     * | bitwise XOR operation on those binary values.                          |
     * | Null values are ignored.                                               |
     * --------------------------------------------------------------------------
     * @param string|int $column
     * @return $this
     * @throws \Exception
     */
    public function bitXor(string|int $column): self
    {
        $this->bitAggregateFunctionClauseBinder('BIT_XOR', $column);

        return $this;
    }

    /**
     * --------------------------------------------------------------------------
     * | An aggregate function returning a string with the combined value.      |
     * | ------------------------------ Use cases ----------------------------- |
     * | groupConcat('column') - creates a string with a concatenated non-null  |
     * | value. Groups separated by comma (,).                                  |
     * | groupConcat('column', '|') - creates a string with a concatenated      |
     * | non-null value. Groups are separated by a vertical line (|)            |
     * | ---------------------------------------------------------------------- |
     * | This is an aggregate function (GROUP BY) that returns a string value   |
     * | if the group contains at least one non-null value. Otherwise it        |
     * | returns null (returns null if there are no non-null values).           |
     * | The separator is not added to the end of the line.                     |
     * | Null values are ignored.                                               |
     * | ---------------------------------------------------------------------- |
     * | Same as "stringAgg" or "listAgg".                                      |
     * --------------------------------------------------------------------------
     * @param string $column
     * @param string $separator
     * @return $this
     */
    public function groupConcat(string $column, string $separator = ','): self
    {
        $this->groupConcatAggregateFunctionClauseBinder($column, $separator);

        return $this;
    }

    /**
     * --------------------------------------------------------------------------
     * | An aggregate function returning a string with the combined distinct    |
     * | (unique) values.                                                       |
     * | ------------------------------ Use cases ----------------------------- |
     * | groupConcatDistinct('column') - creates a string with a concatenated   |
     * | non-null, distinct (unique) values. Groups separated by comma (,)      |
     * | groupConcatDistinct('column', '|') - creates a string with a           |
     * | concatenated non-null, distinct (unique) values. Groups are separated  |
     * | by a vertical line (|).                                                |
     * | ---------------------------------------------------------------------- |
     * | This is an aggregate function (GROUP BY) that returns a string value   |
     * | if the group contains at least one non-null, distinct (unique)         |
     * | values. Otherwise it returns null (returns null if there are no        |
     * | non-null values).                                                      |
     * | The separator is not added to the end of the line.                     |
     * | Null values are ignored.                                               |
     * | ---------------------------------------------------------------------- |
     * | Same as "stringAggDistinct" or "listAggDistinct".                      |
     * --------------------------------------------------------------------------
     * @param string $column
     * @param string $separator
     * @return $this
     */
    public function groupConcatDistinct(string $column, string $separator = ','): self
    {
        $this->groupConcatAggregateFunctionClauseBinder($column, $separator, true);

        return $this;
    }

    /**
     * --------------------------------------------------------------------------
     * | An aggregate function returning a string with the combined value.      |
     * | ------------------------------ Use cases ----------------------------- |
     * | stringAgg('column') - creates a string with a concatenated non-null    |
     * | value. Groups separated by comma (,).                                  |
     * | stringAgg('column', '|') - creates a string with a concatenated        |
     * | non-null value. Groups are separated by a vertical line (|)            |
     * | ---------------------------------------------------------------------- |
     * | This is an aggregate function (GROUP BY) that returns a string value   |
     * | if the group contains at least one non-null value. Otherwise it        |
     * | returns null (returns null if there are no non-null values).           |
     * | The separator is not added to the end of the line.                     |
     * | Null values are ignored.                                               |
     * | ---------------------------------------------------------------------- |
     * | Same as "groupConcat" or "listAgg".                                    |
     * --------------------------------------------------------------------------
     * @param string $column
     * @param string $separator
     * @return $this
     */
    public function stringAgg(string $column, string $separator = ','): self
    {
        $this->groupConcatAggregateFunctionClauseBinder($column, $separator);

        return $this;
    }

    /**
     * --------------------------------------------------------------------------
     * | An aggregate function returning a string with the combined distinct    |
     * | (unique) values.                                                       |
     * | ------------------------------ Use cases ----------------------------- |
     * | stringAggDistinct('column') - creates a string with a concatenated     |
     * | non-null, distinct (unique) values. Groups separated by comma (,).     |
     * | stringAggDistinct('column', '|') - creates a string with a             |
     * | concatenated non-null, distinct (unique) values. Groups are separated  |
     * | by a vertical line (|).                                                |
     * | ---------------------------------------------------------------------- |
     * | This is an aggregate function (GROUP BY) that returns a string value   |
     * | if the group contains at least one non-null, distinct (unique)         |
     * | values. Otherwise it returns null (returns null if there are no        |
     * | non-null values).                                                      |
     * | The separator is not added to the end of the line.                     |
     * | Null values are ignored.                                               |
     * | ---------------------------------------------------------------------- |
     * | Same as "groupConcatDistinct" or "listAggDistinct".                    |
     * --------------------------------------------------------------------------
     * @param string $column
     * @param string $separator
     * @return $this
     */
    public function stringAggDistinct(string $column, string $separator = ','): self
    {
        $this->groupConcatAggregateFunctionClauseBinder($column, $separator, true);

        return $this;
    }

    /**
     * --------------------------------------------------------------------------
     * | An aggregate function returning a string with the combined value.      |
     * | ------------------------------ Use cases ----------------------------- |
     * | listAgg('column') - creates a string with a concatenated non-null      |
     * | value. Groups separated by comma (,).                                  |
     * | listAgg('column', '|') - creates a string with a concatenated          |
     * | non-null value. Groups are separated by a vertical line (|)            |
     * | ---------------------------------------------------------------------- |
     * | This is an aggregate function (GROUP BY) that returns a string value   |
     * | if the group contains at least one non-null value. Otherwise it        |
     * | returns null (returns null if there are no non-null values).           |
     * | The separator is not added to the end of the line.                     |
     * | Null values are ignored.                                               |
     * | ---------------------------------------------------------------------- |
     * | Same as "groupConcat" or "stringAgg".                                  |
     * --------------------------------------------------------------------------
     * @param string $column
     * @param string $separator
     * @return $this
     */
    public function listAgg(string $column, string $separator = ','): self
    {
        $this->groupConcatAggregateFunctionClauseBinder($column, $separator);

        return $this;
    }

    /**
     * --------------------------------------------------------------------------
     * | An aggregate function returning a string with the combined distinct    |
     * | (unique) values.                                                       |
     * | ------------------------------ Use cases ----------------------------- |
     * | listAggDistinct('column') - creates a string with a concatenated       |
     * | non-null, distinct (unique) values. Groups separated by comma (,).     |
     * | listAggDistinct('column', '|') - creates a string with a               |
     * | concatenated non-null, distinct (unique) values. Groups are separated  |
     * | by a vertical line (|).                                                |
     * | ---------------------------------------------------------------------- |
     * | This is an aggregate function (GROUP BY) that returns a string value   |
     * | if the group contains at least one non-null, distinct (unique)         |
     * | values. Otherwise it returns null (returns null if there are no        |
     * | non-null values).                                                      |
     * | The separator is not added to the end of the line.                     |
     * | Null values are ignored.                                               |
     * | ---------------------------------------------------------------------- |
     * | Same as "groupConcatDistinct" or "stringAggDistinct".                  |
     * --------------------------------------------------------------------------
     * @param string $column
     * @param string $separator
     * @return $this
     */
    public function listAggDistinct(string $column, string $separator = ','): self
    {
        $this->groupConcatAggregateFunctionClauseBinder($column, $separator, true);

        return $this;
    }

    /**
     * --------------------------------------------------------------------------
     * | An aggregate function that aggregates a set of results into a single   |
     * | JSON array, the elements of which consist of strings.                  |
     * | ------------------------------ Use cases ----------------------------- |
     * | jsonArrayAgg('column') - aggregates a set of results as a single JSON  |
     * | array whose elements consist of the values of column "column".         |
     * | ---------------------------------------------------------------------- |
     * | The order of the elements in this array is undefined.                  |
     * | The function operates on a column or expression that results in a      |
     * | single value. Returns null if the result contains no rows or if there  |
     * | is an error. If column or expression is NULL, the function returns an  |
     * | array of [null] JSON elements.                                         |
     * | ---------------------------------------------------------------------- |
     * | Same as "jsonGroupArray" or "jsonAgg" or "jsonArray".                  |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return $this
     */
    public function jsonArrayAgg(string $column): self
    {
        $aggregateFunction = match ($this->getDriver()) {
            AvailableDbmsDrivers::SQLITE => 'JSON_GROUP_ARRAY',
            AvailableDbmsDrivers::POSTGRESQL => 'JSON_AGG',
            AvailableDbmsDrivers::MS_SQL_SERVER => 'JSON_ARRAY',
            default => 'JSON_ARRAYAGG'
        };

        $this->aggregateFunctionsClauseBinder($aggregateFunction, $column);

        return $this;
    }

    /**
     * --------------------------------------------------------------------------
     * | An aggregate function that aggregates a set of results into a single   |
     * | JSON array, the elements of which consist of strings.                  |
     * | ------------------------------ Use cases ----------------------------- |
     * | jsonGroupArray('column') - aggregates a set of results as a single     |
     * | JSON array whose elements consist of the values of column "column".    |
     * | ---------------------------------------------------------------------- |
     * | The order of the elements in this array is undefined.                  |
     * | The function operates on a column or expression that results in a      |
     * | single value. Returns null if the result contains no rows or if there  |
     * | is an error. If column or expression is NULL, the function returns an  |
     * | array of [null] JSON elements.                                         |
     * | ---------------------------------------------------------------------- |
     * | Same as "jsonArrayAgg" or "jsonAgg" or "jsonArray".                    |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return $this
     */
    public function jsonGroupArray(string $column): self
    {
        $aggregateFunction = match ($this->getDriver()) {
            AvailableDbmsDrivers::SQLITE => 'JSON_GROUP_ARRAY',
            AvailableDbmsDrivers::POSTGRESQL => 'JSON_AGG',
            AvailableDbmsDrivers::MS_SQL_SERVER => 'JSON_ARRAY',
            default => 'JSON_ARRAYAGG'
        };

        $this->aggregateFunctionsClauseBinder($aggregateFunction, $column);

        return $this;
    }

    /**
     * --------------------------------------------------------------------------
     * | An aggregate function that aggregates a set of results into a single   |
     * | JSON array, the elements of which consist of strings.                  |
     * | ------------------------------ Use cases ----------------------------- |
     * | jsonAgg('column') - aggregates a set of results as a single JSON array |
     * | whose elements consist of the values of column "column".               |
     * | ---------------------------------------------------------------------- |
     * | The order of the elements in this array is undefined.                  |
     * | The function operates on a column or expression that results in a      |
     * | single value. Returns null if the result contains no rows or if there  |
     * | is an error. If column or expression is NULL, the function returns an  |
     * | array of [null] JSON elements.                                         |
     * | ---------------------------------------------------------------------- |
     * | Same as "jsonArrayAgg" or "jsonGroupArray" or "jsonArray".             |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return $this
     */
    public function jsonAgg(string $column): self
    {
        $aggregateFunction = match ($this->getDriver()) {
            AvailableDbmsDrivers::SQLITE => 'JSON_GROUP_ARRAY',
            AvailableDbmsDrivers::POSTGRESQL => 'JSON_AGG',
            AvailableDbmsDrivers::MS_SQL_SERVER => 'JSON_ARRAY',
            default => 'JSON_ARRAYAGG'
        };

        $this->aggregateFunctionsClauseBinder($aggregateFunction, $column);

        return $this;
    }

    /**
     * --------------------------------------------------------------------------
     * | An aggregate function that aggregates a set of results into a single   |
     * | JSON array, the elements of which consist of strings.                  |
     * | ------------------------------ Use cases ----------------------------- |
     * | jsonArray('column') - aggregates a set of results as a single JSON     |
     * | array whose elements consist of the values of column "column".         |
     * | ---------------------------------------------------------------------- |
     * | The order of the elements in this array is undefined.                  |
     * | The function operates on a column or expression that results in a      |
     * | single value. Returns null if the result contains no rows or if there  |
     * | is an error. If column or expression is NULL, the function returns an  |
     * | array of [null] JSON elements.                                         |
     * | ---------------------------------------------------------------------- |
     * | Same as "jsonArrayAgg" or "jsonGroupArray" or "jsonAgg".               |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return $this
     */
    public function jsonArray(string $column): self
    {
        $aggregateFunction = match ($this->getDriver()) {
            AvailableDbmsDrivers::SQLITE => 'JSON_GROUP_ARRAY',
            AvailableDbmsDrivers::POSTGRESQL => 'JSON_AGG',
            AvailableDbmsDrivers::MS_SQL_SERVER => 'JSON_ARRAY',
            default => 'JSON_ARRAYAGG'
        };

        $this->aggregateFunctionsClauseBinder($aggregateFunction, $column);

        return $this;
    }

    /**
     * --------------------------------------------------------------------------
     * | An aggregate function returning a JSON object containing key-value     |
     * | pairs.                                                                 |
     * | ------------------------------ Use cases ----------------------------- |
     * | jsonObjectAgg('keyColumn', 'valueColumn') - aggregates into a JSON     |
     * | object containing key-value pairs. Value from keyColumn as key a value |
     * | from valueColumn as value.                                             |
     * |                                                                        |
     * | -------------------- Use cases for MS SQL Server --------------------- |
     * | jsonObjectAgg('key1:column1', 'key2:column1', 'key3:column1') -        |
     * | aggregates into a JSON object containing key-value pairs.              |
     * | ---------------------------------------------------------------------- |
     * | Duplicate key processing. When the result of this function is          |
     * | normalized, values that have duplicate keys are discarded. In          |
     * | accordance with the database driver's JSON data type specification,    |
     * | which does not allow duplicate keys, only the last encountered value   |
     * | is used with this key in the returned object ("last duplicate key      |
     * | wins"). This means that the result of using this function may depend   |
     * | on the order in which the rows are returned, which is not guaranteed.  |
     * | Returns null if the result contains no rows or if there is an error.   |
     * | An error occurs if any key name is null or the number of arguments is  |
     * | not equal to 2.                                                        |
     * | ---------------------------------------------------------------------- |
     * | Same as "jsonObject".                                                  |
     * --------------------------------------------------------------------------
     * @param string $keyColumn
     * @param string ...$valueColumn
     * @return $this
     * @throws Exception
     */
    public function jsonObjectAgg(string $keyColumn, string ...$valueColumn): self
    {
        $this->jsonObjectAggregateFunctionClauseBinder($keyColumn, $valueColumn);

        return $this;
    }

    /**
     * --------------------------------------------------------------------------
     * | An aggregate function returning a JSON object containing key-value     |
     * | pairs.                                                                 |
     * | ------------------------------ Use cases ----------------------------- |
     * | jsonObject('keyColumn', 'valueColumn') - aggregates into a JSON        |
     * | object containing key-value pairs. Value from keyColumn as key a value |
     * | from valueColumn as value.                                             |
     * |                                                                        |
     * | -------------------- Use cases for MS SQL Server --------------------- |
     * | jsonObject('key1:column1', 'key2:column1', 'key3:column1') -           |
     * | aggregates into a JSON object containing key-value pairs.              |
     * | ---------------------------------------------------------------------- |
     * | Duplicate key processing. When the result of this function is          |
     * | normalized, values that have duplicate keys are discarded. In          |
     * | accordance with the database driver's JSON data type specification,    |
     * | which does not allow duplicate keys, only the last encountered value   |
     * | is used with this key in the returned object ("last duplicate key      |
     * | wins"). This means that the result of using this function may depend   |
     * | on the order in which the rows are returned, which is not guaranteed.  |
     * | Returns null if the result contains no rows or if there is an error.   |
     * | An error occurs if any key name is null or the number of arguments is  |
     * | not equal to 2.                                                        |
     * | ---------------------------------------------------------------------- |
     * | Same as "jsonObjectAgg".                                               |
     * --------------------------------------------------------------------------
     * @param string $keyColumn
     * @param string ...$valueColumn
     * @return $this
     * @throws Exception
     */
    public function jsonObject(string $keyColumn, string ...$valueColumn): self
    {
        $this->jsonObjectAggregateFunctionClauseBinder($keyColumn, $valueColumn);

        return $this;
    }

    /**
     * --------------------------------------------------------------------------
     * | An aggregate function returning the population standard deviation.     |
     * | ------------------------------ Use cases ----------------------------- |
     * | stdDev('column') - population standard deviation of all input values.  |
     * | ---------------------------------------------------------------------- |
     * | The standard deviation shows how much deviation there is from the mean |
     * | or mean. In other words, it is the square root of the variance.        |
     * | stdDev is used when the group of numbers being evaluated are only a    |
     * | partial sampling of the whole population. The denominator for dividing |
     * | the sum of squared deviations is N-1, where N is the number of         |
     * | observations ( a count of items in the data set ). Technically,        |
     * | subtracting the 1 is referred to as "non-biased".                      |
     * | The function only processes non-null values. That is, null values are  |
     * | ignored by the function.                                               |
     * | Sqlite driver does not support this feature.                           |
     * | ---------------------------------------------------------------------- |
     * | Same as "stdDevPop".                                                   |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return $this
     * @throws Exception
     */
    public function stdDev(string $column): self
    {
        $this->standardDeviationAggregateFunctionClauseBinder($column);

        return $this;
    }

    /**
     * --------------------------------------------------------------------------
     * | An aggregate function returning the population standard deviation.     |
     * | ------------------------------ Use cases ----------------------------- |
     * | stdDevPop('column') - population standard deviation of all input       |
     * | values.                                                                |
     * | ---------------------------------------------------------------------- |
     * | The standard deviation shows how much deviation there is from the mean |
     * | or mean. In other words, it is the square root of the variance.        |
     * | stdDevPop is used when the group of numbers being evaluated are only   |
     * | a partial sampling of the whole population. The denominator for        |
     * | dividing the sum of squared deviations is N-1, where N is the number   |
     * | of observations ( a count of items in the data set ). Technically,     |
     * | subtracting the 1 is referred to as "non-biased".                      |
     * | The function only processes non-null values. That is, null values are  |
     * | ignored by the function.                                               |
     * | Sqlite driver does not support this feature.                           |
     * | ---------------------------------------------------------------------- |
     * | Same as "stdDev".                                                      |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return $this
     * @throws \Exception
     */
    public function stdDevPop(string $column): self
    {
        $this->standardDeviationAggregateFunctionClauseBinder($column);

        return $this;
    }

    /**
     * --------------------------------------------------------------------------
     * | An aggregate function that returns the standard deviation of a sample  |
     * | of input values.                                                       |
     * | ------------------------------ Use cases ----------------------------- |
     * | stdDevSamp('column') - standard deviation of a sample of input values. |
     * | ---------------------------------------------------------------------- |
     * | stdDevSamp is used when the group of numbers being evaluated is        |
     * | complete—the entire set of values. In this case, 1 is not subtracted,  |
     * | and the denominator for dividing the sum of squared deviations is      |
     * | simply N, the number of observations (the number of elements in the    |
     * | data set). Technically this is called "biased".                        |
     * | The function only processes non-null values. That is, null values are  |
     * | ignored by the function.                                               |
     * | Sqlite driver does not support this feature.                           |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return $this
     * @throws Exception
     */
    public function stdDevSamp(string $column): self
    {
        $this->standardDeviationAggregateFunctionClauseBinder($column, true);

        return $this;
    }

    /**
     * --------------------------------------------------------------------------
     * | An aggregate function returning the standard variance of the input     |
     * | values.                                                                |
     * | ------------------------------ Use cases ----------------------------- |
     * | variance('column') - standard variance of column "column".             |
     * | ---------------------------------------------------------------------- |
     * | Population variance is a statistical measure that quantifies the       |
     * | standard deviation of values from the mean in a data set.              |
     * | The function only processes non-null values. That is, null values are  |
     * | ignored by the function.                                               |
     * | Sqlite driver does not support this feature.                           |
     * | ---------------------------------------------------------------------- |
     * | Same as "varPop".                                                      |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return $this
     * @throws \Exception
     */
    public function variance(string $column): self
    {
        $this->varianceAggregateFunctionClauseBinder($column);

        return $this;
    }

    /**
     * --------------------------------------------------------------------------
     * | An aggregate function returning the standard variance of the input     |
     * | values.                                                                |
     * | ------------------------------ Use cases ----------------------------- |
     * | varPop('column') - standard variance of column "column".               |
     * | ---------------------------------------------------------------------- |
     * | Population variance is a statistical measure that quantifies the       |
     * | standard deviation of values from the mean in a data set.              |
     * | The function only processes non-null values. That is, null values are  |
     * | ignored by the function.                                               |
     * | Sqlite driver does not support this feature.                           |
     * | ---------------------------------------------------------------------- |
     * | Same as "variance".                                                    |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return $this
     * @throws Exception
     */
    public function varPop(string $column): self
    {
        $this->varianceAggregateFunctionClauseBinder($column);

        return $this;
    }

    /**
     * --------------------------------------------------------------------------
     * | An aggregate function returning the sample variance of the input       |
     * | values.                                                                |
     * | ------------------------------ Use cases ----------------------------- |
     * | varSamp('column') - sample variance of column "column".                |
     * | ---------------------------------------------------------------------- |
     * | "varSamp" considers the entire dataset rather than just a sample.      |
     * | Sample variance is a statistical measure that indicates the spread or  |
     * | dispersion of a dataset.                                               |
     * | The function only processes non-null values. That is, null values are  |
     * | ignored by the function.                                               |
     * | Sqlite driver does not support this feature.                           |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return $this
     * @throws Exception
     */
    public function varSamp(string $column): self
    {
        $this->varianceAggregateFunctionClauseBinder($column, true);

        return $this;
    }

    /**
     * --------------------------------------------------------------------------
     * | A predicate is an expression that evaluates to true, false, or         |
     * | unknown.                                                               |
     * --------------------------------------------------------------------------
     */

    /**
     * --------------------------------------------------------------------------
     * | A predictive function that verifies for the presence of a record       |
     * | matching the requirements in a sub query.                              |
     * | ------------------------------ Use cases ----------------------------- |
     * | exists() - verifies whether a record exists that matches the           |
     * | requirements of the query that was written before the exists call.     |
     * --------------------------------------------------------------------------
     * @return bool
     */
    public function exists(): bool
    {
        return $this->existsClauseBinder();
    }

    /**
     * --------------------------------------------------------------------------
     * | A predictive function that verifies for the absence of a matching      |
     * | record in a sub query.                                                 |
     * | ------------------------------ Use cases ----------------------------- |
     * | doesntExists() - verifies whether the corresponding record is missing  |
     * | requirements of the query that was written before calling              |
     * | doesntExists.                                                          |
     * --------------------------------------------------------------------------
     * @return bool
     */
    public function doesntExists(): bool
    {
        return !$this->existsClauseBinder();
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to specify the table that the query builder should work on.     |
     * | ------------------------------ Use cases ----------------------------- |
     * | from('table') - specifies the table the query builder should work      |
     * | with.                                                                  |
     * | ---------------------------------------------------------------------- |
     * | Same as "table".                                                       |
     * --------------------------------------------------------------------------
     * @param string $table
     * @return $this
     */
    public function from(string $table): self
    {
        $this->fromClauseBinder($table);

        return $this;
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause to specify the table that the query builder should work on.     |
     * | ------------------------------ Use cases ----------------------------- |
     * | table('table') - specifies the table the query builder should work     |
     * | with.                                                                  |
     * | ---------------------------------------------------------------------- |
     * | Same as "from".                                                        |
     * --------------------------------------------------------------------------
     * @param string $table
     * @return $this
     */
    public function table(string $table): self
    {
        $this->fromClauseBinder($table);

        return $this;
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause for specifying conditions in a request.                         |
     * | ------------------------------ Use cases ----------------------------- |
     * | -- The below variations retrieves records that match a condition --    |
     * | | where('column', '=', 'value')                                   |    |
     * | | where('column', 'value') - this expression uses the "="         |    |
     * | | operator for condition.                                         |    |
     * | | where(['column', '=', 'value'])                                 |    |
     * | | where(['column' => 'value']) - this expression uses the "="     |    |
     * | | operator for condition.                                         |    |
     * | | where(['column1' => 'value1', 'column2' => 'value2']) - this    |    |
     * | | expression uses the "=" operator for condition and logical      |    |
     * | | "AND" operator for combine.                                     |    |
     * | | where(function ($query) { $query->... }, '=', 'value') - this   |    |
     * | | expression uses the result of the sub query to compare with     |    |
     * | | "value".                                                        |    |
     * | | where(function ($query) { $query->... }, 'value') - this        |    |
     * | | expression uses the result of the sub query to compare with     |    |
     * | | "value". This expression uses the "=" operator for condition.   |    |
     * | | where('column', '=', function ($query) { $query->... }) - this  |    |
     * | | expression uses the result of the sub query to compare with     |    |
     * | | "column".                                                       |    |
     * | | where('column', function ($query) { $query->... }) - this       |    |
     * | | expression uses the result of the sub query for insertion       |    |
     * | | instead of the "value" for comparison. This expression uses     |    |
     * | | the "=" operator for condition.                                 |    |
     * | -------------------------------------------------------------------    |
     * --------------------------------------------------------------------------
     * @param string|array|callable $column
     * @param string|int|float|callable|null $operator
     * @param string|int|float|callable|null $value
     * @return $this
     * @throws \Exception
     */
    public function where(string|array|callable $column,
                          string|int|float|callable|null $operator = null,
                          string|int|float|callable|null $value = ''): self
    {
        $this->baseConditionClauseBinder('', 'where', $column, $operator, $value);

        return $this;
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause for specifying conditions in a request. Verifies  whether the   |
     * | column value is between the given values.                              |
     * | ------------------------------ Use cases ----------------------------- |
     * | -- The below variations retrieves records that match a condition --    |
     * | | whereBetween('column', '0', '10')                               |    |
     * | | whereBetween('column', [0, 10])                                 |    |
     * | -------------------------------------------------------------------    |
     * --------------------------------------------------------------------------
     * @param string $column
     * @param array|string|int|float $range
     * @param string|int|float $endOfRange
     * @return $this
     * @throws Exception
     */
    public function whereBetween(string $column,
                                 array|string|int|float $range = '',
                                 string|int|float $endOfRange = ''): self
    {
        $this->whereBetweenClauseBinder('', $column, $range, $endOfRange);

        return $this;
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause for specifying conditions in a request. Verifies that a         |
     * | column's value is between the two values of two columns in the same    |
     * | table row.                                                             |
     * | ------------------------------ Use cases ----------------------------- |
     * | -- The below variations retrieves records that match a condition --    |
     * | | whereBetweenColumns('column', 'column1', 'column2')             |    |
     * | | whereBetweenColumns('column', ['column1', 'column2'])           |    |
     * | -------------------------------------------------------------------    |
     * --------------------------------------------------------------------------
     * @param string $column
     * @param array|string|int|float $range
     * @param string|int|float $endOfRange
     * @return $this
     * @throws Exception
     */
    public function whereBetweenColumns(string $column,
                                        array|string|int|float $range = '',
                                        string|int|float $endOfRange = ''): self
    {
        $this->whereBetweenClauseBinder('', $column, $range, $endOfRange, false, true);

        return $this;
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause for specifying conditions in a request. Verifies that a given   |
     * | column's value is contained within the given array.                    |
     * | ------------------------------ Use cases ----------------------------- |
     * | whereIn('column', ['value1', 'value2']) - retrieves records that       |
     * | match a condition.                                                     |
     * --------------------------------------------------------------------------
     * @param string $column
     * @param array $setOfSupposedVariables
     * @return $this
     * @throws Exception
     */
    public function whereIn(string $column, array $setOfSupposedVariables = []): self
    {
        $this->whereInClauseBinder('', $column, $setOfSupposedVariables);

        return $this;
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause for specifying conditions in a request. Verifies that the value |
     * | of the given column is NULL.                                           |
     * | ------------------------------ Use cases ----------------------------- |
     * | whereNull('column') - retrieves records that match a condition.        |
     * --------------------------------------------------------------------------
     * @param string $column
     * @return $this
     */
    public function whereNull(string $column): self
    {
        $this->whereNullClauseBinder('', $column);

        return $this;
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause for specifying conditions in a request. Verifies whether there  |
     * | is a row corresponding to the sub query written in the closure.        |
     * | ------------------------------ Use cases ----------------------------- |
     * | whereExists(function ($query) { $query->... }) - retrieves record      |
     * | that match a condition.                                                |
     * --------------------------------------------------------------------------
     * @param callable $callback
     * @return $this
     */
    public function whereExists(callable $callback): self
    {
        $this->whereExistsClauseBinder('', $callback);

        return $this;
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause for specifying conditions in a request. Verifies whether there  |
     * | is a row corresponding that the selected comparison of the selected    |
     * | columns each other in row.                                             |
     * | ------------------------------ Use cases ----------------------------- |
     * | -- The below variations retrieves records that match a condition --    |
     * | | whereColumn('column1', '=', 'column2')                          |    |
     * | | whereColumn('column1', 'column2') - this expression uses the    |    |
     * | | "=" operator for condition.                                     |    |
     * | | whereColumn(['column1', '=', 'column2'])                        |    |
     * | | whereColumn(['column1', 'column2']) - this expression uses the  |    |
     * | | "=" operator for condition.                                     |    |
     * | | where(['column1' => 'column2']) - this expression uses the "="  |    |
     * | | operator for condition.                                         |    |
     * | | where(['column1' => 'column2', 'column3' => 'column4']) - this  |    |
     * | | expression uses the "=" operator for condition and logical      |    |
     * | | "AND" operator for combine.                                     |    |
     * | | where([                                                         |    |
     * | |       ['column1', '=', 'column2'],                              |    |
     * | |       ['column3', 'column4']                                    |    |
     * | |       ['column5' => 'column6']                                  |    |
     * | |       ['column5' => 'column6']                                  |    |
     * | | ]) - If you pass an array as the first argument and pass other  |    |
     * | | nested arrays in it then you can use all the above options for  |    |
     * | | nested arrays. This expression uses the logical "AND" operator  |    |
     * | | for combine and the conditional operator will be chosen         |    |
     * | | depending on the nested array, depends on which of the above    |    |
     * | | notation types you use (the conditional statement will be       |    |
     * | | selected according to the conditions specified after the        |    |
     * | | notation type).                                                 |    |
     * | -------------------------------------------------------------------    |
     * --------------------------------------------------------------------------
     * @param string|array $firstColumn
     * @param string|null $operator
     * @param string|null $secondColumn
     * @return $this
     * @throws Exception
     */
    public function whereColumn(string|array $firstColumn, string|null $operator = null, string|null $secondColumn = null): self
    {
        $this->whereColumnClauseBinder('', $firstColumn, $operator, $secondColumn);

        return $this;
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause for specifying conditions in a request. Performs a full-text    |
     * | search for the specified text in the specified columns with or without |
     * | the specified parameters.                                              |
     * | ------------------------------ Use cases ----------------------------- |
     * | ----------- The below variations only for MySQL DB driver ---------    |
     * | | whereFullText('column', 'Target text for search') - performs a  |    |
     * | | full-text search of text by column.                             |    |
     * | | whereFullText(                                                  |    |
     * | |       ['column1', 'column2'],                                   |    |
     * | |       'Target text for search'                                  |    |
     * | | ) - performs full text search of text by columns.               |    |
     * | | whereFullText(                                                  |    |
     * | |       ['column1', 'column2'],                                   |    |
     * | |       'Target text for search'                                  |    |
     * | |        FullTextSearchModifiers::BOOLEAN_MODE                    |    |
     * | | ) - performs full text search of text by columns.               |    |
     * | | You can pass a full-text search modifier as third argument.     |    |
     * | | Modifiers are passed from the FullTextSearchModifiers class.    |    |
     * | | If you don't pass a third argument, the NATURAL_LANGUAGE_MODE   |    |
     * | | modifier is used by default.                                    |    |
     * | | The following modifiers exist.                                  |    |
     * | |       NATURAL_LANGUAGE_MODE - natural language full-text        |    |
     * | |       search interprets the search string as a free text        |    |
     * | |       (natural human language). So there are no special         |    |
     * | |       characters here except " (double quote). For each row in  |    |
     * | |       the table, mode returns a relevance value, that is, a     |    |
     * | |       similarity measure between the search string (given as    |    |
     * | |       the argument to function) and the text in that row in     |    |
     * | |       specified columns.                                        |    |
     * | |                                                                 |    |
     * | |       WITH_QUERY_EXPANSION - with query expansion (also known   |    |
     * | |       as automatic relevance feedback or blind query expansion) |    |
     * | |       search interprets the search string when a search phrase  |    |
     * | |       is too short which often means that the user is relying   |    |
     * | |       on implied knowledge that the full-text search engine     |    |
     * | |       lacks. For example, a user searching for “database” may   |    |
     * | |       really mean that “MySQL”, “Oracle”, “DB2”, and “RDBMS”    |    |
     * | |       all are phrases that should match “databases” and should  |    |
     * | |       be returned, too. This is implied knowledge. Blind query  |    |
     * | |       expansion (also known as automatic relevance feedback) is |    |
     * | |       enabled by adding WITH_QUERY_EXPANSION or                 |    |
     * | |       NATURAL_LANGUAGE_MODE_WITH_QUERY_EXPANSION following the  |    |
     * | |       search phrase. It works by performing the search twice,   |    |
     * | |       where the search phrase for the second search is the      |    |
     * | |       original search phrase concatenated with the few most     |    |
     * | |       highly relevant documents from the first search. Thus, if |    |
     * | |       one of these documents contains the word “databases” and  |    |
     * | |       the word “MySQL”, the second search finds the documents   |    |
     * | |       that contain the word “MySQL” even if they  do not        |    |
     * | |       contain the word “database”.                              |    |
     * | |                                                                 |    |
     * | |       BOOLEAN_MODE - a boolean search interprets the search     |    |
     * | |       string using the rules of a special query language.       |    |
     * | |       The string contains the words to search for. It can       |    |
     * | |       also contain operators that specify requirements such     |    |
     * | |       that a word must be present or absent in matching rows,   |    |
     * | |       or that it should be weighted higher or lower than usual. |    |
     * | |       Certain common words (stop words) are omitted from the    |    |
     * | |       search index and do not match if present in the search    |    |
     * | |       string. Do not use the 50% threshold that applies to      |    |
     * | |       MyISAM search indexes.                                    |    |
     * | |       Do not automatically sort rows in order of decreasing     |    |
     * | |       relevance.                                                |    |
     * | |       Boolean queries against a MyISAM search index can work    |    |
     * | |       even without a full-text index.                           |    |
     * | |       The minimum and maximum word length full-text parameters  |    |
     * | |       apply:                                                    |    |
     * | |       For InnoDB search indexes, innodb_ft_min_token_size and   |    |
     * | |       innodb_ft_max_token_size.                                 |    |
     * | |       For MyISAM search indexes, ft_min_word_len and            |    |
     * | |       ft_max_word_len. InnoDB full-text search does not support |    |
     * | |       the use of multiple operators on a single search word.    |    |
     * | |       The boolean full-text search supports the following       |    |
     * | |       operators:                                                |    |
     * | |              (no operator) - By default, the word is optional,  |    |
     * | |              but the rows that contain it are rated higher.     |    |
     * | |                                                                 |    |
     * | |              + - A leading plus sign indicates that a word must |    |
     * | |              be present in each row that is returned.           |    |
     * | |                                                                 |    |
     * | |              - - A leading minus sign indicates that a          |    |
     * | |              particular word must not be present in any of      |    |
     * | |              the rows that are returned. The "-" operator acts  |    |
     * | |              only to exclude rows that are otherwise matched    |    |
     * | |              by other search terms.                             |    |
     * | |                                                                 |    |
     * | |              > < - These two operators are used to change a     |    |
     * | |              word's contribution to the relevance value that is |    |
     * | |              assigned to a row. The > operator increases the    |    |
     * | |              contribution and the < operator decreases it.      |    |
     * | |                                                                 |    |
     * | |              ( ) - Parentheses group words into subexpressions. |    |
     * | |              Parenthesized groups can be nested.                |    |
     * | |                                                                 |    |
     * | |              ~ - A leading tilde acts as a negation operator,   |    |
     * | |              causing the word's contribution to the row's       |    |
     * | |              relevance to be negative.                          |    |
     * | |                                                                 |    |
     * | |              * - The asterisk serves as the truncation          |    |
     * | |              (or wildcard) operator. Unlike the other           |    |
     * | |              operators, it is appended to the word to be        |    |
     * | |              affected. Words match if they begin with the word  |    |
     * | |              preceding the * operator.                          |    |
     * | |                                                                 |    |
     * | |              " - A phrase that is enclosed within double quote  |    |
     * | |              (“"”) characters matches only rows that contain    |    |
     * | |              the phrase literally, as it was typed.             |    |
     * | |              The remaining arguments, even if you specify them, |    |
     * | |              will not be used.                                  |    |
     * | -------------------------------------------------------------------    |
     * | ------- The below variations only for PostgreSQL DB driver --------    |
     * | | Third argument (searchModifier) - as search modifier you can    |    |
     * | | pass a language name. If you do not specify this argument or    |    |
     * | | specify an empty string, the language will not be listed for    |    |
     * | | full-text search.                                               |    |
     * | |                                                                 |    |
     * | | Fourth argument (highlighting) - highlighting search words in   |    |
     * | | text. If you set true this argument or pass array with          |    |
     * | | highlighting configs (which implies that you have enabled       |    |
     * | | highlighting), function accepts a  document along with a query, |    |
     * | | and returns an excerpt from the document in which terms from    |    |
     * | | the query are highlighted. Specifically, the function will use  |    |
     * | | the query to select relevant text fragments, and then highlight |    |
     * | | all words that appear in the query, even if those word          |    |
     * | | positions do not match the query's restrictions.                |    |
     * | | If you passed an array with configurations, then they will be   |    |
     * | | used, and if not (you passed just true), then the default will  |    |
     * | | be used default_text_search_config configuration.               |    |
     * | |       MaxWords, MinWords (integers): these numbers determine    |    |
     * | |       the longest and shortest headlines to output. The default |    |
     * | |       values are 35 and 15.                                     |    |
     * | |                                                                 |    |
     * | |       ShortWord (integer): words of this length or less will    |    |
     * | |       be dropped at the start and end of a headline, unless     |    |
     * | |       they are query terms. The default value of three          |    |
     * | |       eliminates common English articles.                       |    |
     * | |                                                                 |    |
     * | |       HighlightAll (boolean): if true the whole document        |    |
     * | |       will be used as the headline, ignoring the preceding      |    |
     * | |       three parameters. The default is false.                   |    |
     * | |                                                                 |    |
     * | |       MaxFragments (integer): maximum number of text            |    |
     * | |       fragments to display. The default value of zero selects   |    |
     * | |       a non-fragment-based headline generation method. A value  |    |
     * | |       greater than zero selects fragment-based headline         |    |
     * | |       generation (see below).                                   |    |
     * | |                                                                 |    |
     * | |       StartSel, StopSel (strings): the strings with which       |    |
     * | |       to delimit query words appearing in the document, to      |    |
     * | |       distinguish them from other excerpted words. The default  |    |
     * | |       values are “<b>” and “</b>”, which can be suitable for    |    |
     * | |       HTML output.                                              |    |
     * | |                                                                 |    |
     * | |       FragmentDelimiter (string): When more than one fragment   |    |
     * | |       is displayed, the fragments will be separated by this     |    |
     * | |       string. The default is “ ... ”.                           |    |
     * | | Examples:                                                       |    |
     * | |       whereFullText(                                            |    |
     * | |              column or columns array,                           |    |
     * | |              'Target text for search',                          |    |
     * | |              'language name or empty string'                    |    |
     * | |              true                                               |    |
     * | |       )                                                         |    |
     * | |                                                                 |    |
     * | |       whereFullText(                                            |    |
     * | |              column or columns array,                           |    |
     * | |              'Target text for search',                          |    |
     * | |              'language name or empty string'                    |    |
     * | |              ['Tag' => 'mark', 'MaxWords' => 10]                |    |
     * | |       )                                                         |    |
     * | |                                                                 |    |
     * | | Fifth argument (rankingColumn) - you can specify a column or    |    |
     * | | several columns by which search results will be ranked. If you  |    |
     * | | specify a column or columns for this argument, they must be     |    |
     * | | included in the first argument.                                 |    |
     * | | You can also pass weights for columns, they should be passed in |    |
     * | | the first argument as an associative array. Weights offers the  |    |
     * | | ability to weigh word instances more or less heavily depending  |    |
     * | | on how they are labeled. The weight arrays specify how heavily  |    |
     * | | to weigh each category of word, in the order.                   |    |
     * | | Typically weights are used to mark words from special areas of  |    |
     * | | the document, like the title or an initial abstract, so they    |    |
     * | | can be treated with more or less importance than words in the   |    |
     * | | document body.                                                  |    |
     * | | Weights:                                                        |    |
     * | |       A, B, C, D (also you can specify the weights in           |    |
     * | |       lowercase)                                                |    |
     * | | If you specified a weight for at least one column, then you     |    |
     * | | must indicate the weights for all because when even one weight  |    |
     * | | is specified, the function understands that this is an array    |    |
     * | | with weights and it must validate all the weights, and if at    |    |
     * | | least one column does not have a weight, then during validation |    |
     * | | when the function reaches this column and validates an empty    |    |
     * | | string, it will find that there is no such value for weight in  |    |
     * | | PostgreSQL and throw an exception.                              |    |
     * | | But if you want to give weight to one of several columns and    |    |
     * | | use it for ranking and not use the other for ranking, then you  |    |
     * | | can give weight to both columns to avoid exceptions and in the  |    |
     * | | fifth argument use only those columns that you need. in this    |    |
     * | | case the weights of the columns that are not used in ranked     |    |
     * | | will be skipped.                                                |    |
     * | | Ranking attempts to measure how relevant documents are to a     |    |
     * | | particular query, so that when there are many matches the most  |    |
     * | | relevant ones can be shown first. Ranking functions, take into  |    |
     * | | account lexical, proximity, and structural information, that    |    |
     * | | is, they consider how often the query terms appear in the       |    |
     * | | document, how close together the terms are in the document,     |    |
     * | | and how important is the part of the document where they occur. |    |
     * | | This function requires lexeme positional information to perform |    |
     * | | its calculation. Therefore, it ignores any “stripped” lexemes   |    |
     * | | in the ts_vector. If there are no un-stripped lexemes in the    |    |
     * | | input, the result will be zero.                                 |    |
     * | | Examples:                                                       |    |
     * | |       whereFullText(                                            |    |
     * | |              'column1',                                         |    |
     * | |              'Target text for search',                          |    |
     * | |              'language name or empty string',                   |    |
     * | |              highlighting true, false or configs array,         |    |
     * | |              'column1'                                          |    |
     * | |       )                                                         |    |
     * | |                                                                 |    |
     * | |       whereFullText(                                            |    |
     * | |              ['column1', 'column2'],                            |    |
     * | |              'Target text for search',                          |    |
     * | |              'language name or empty string',                   |    |
     * | |              highlighting true, false or configs array,         |    |
     * | |              'column1'                                          |    |
     * | |       )                                                         |    |
     * | |                                                                 |    |
     * | |       whereFullText(                                            |    |
     * | |              ['column1', 'column2', 'column3'],                 |    |
     * | |              'Target text for search',                          |    |
     * | |              'language name or empty string',                   |    |
     * | |              highlighting true, false or configs array,         |    |
     * | |              ['column1', 'column2']                             |    |
     * | |       )                                                         |    |
     * | |                                                                 |    |
     * | |       whereFullText(                                            |    |
     * | |              ['column1' => 'A', 'column2' => 'B'],              |    |
     * | |              'Target text for search',                          |    |
     * | |              'language name or empty string',                   |    |
     * | |              highlighting true, false or configs array,         |    |
     * | |              ['column1', 'column2']                             |    |
     * | |       )                                                         |    |
     * | |                                                                 |    |
     * | |       whereFullText(                                            |    |
     * | |              ['column1' => 'A', 'column2' => 'B'],              |    |
     * | |              'Target text for search',                          |    |
     * | |              'language name or empty string',                   |    |
     * | |              highlighting true, false or configs array,         |    |
     * | |              'column1'                                          |    |
     * | |       )                                                         |    |
     * | |                                                                 |    |
     * | | Sixth argument (normalization bitmask) - you can specify an     |    |
     * | | normalization option as integer or array that specifies whether |    |
     * | | and how a document's length should impact its rank. The integer |    |
     * | | option controls several behaviors, so it is a bit mask. You can |    |
     * | | specify one or more behaviors (normalization bitmasks) using    |    |
     * | | array.                                                          |    |
     * | | You can also specify the normalization bitmask (just bitmask or |    |
     * | | elements array of bitmasks) elements as string instead of       |    |
     * | | integer type values.                                            |    |
     * | | Normalization bitmasks:                                         |    |
     * | |       0 - ignores the document length.                          |    |
     * | |                                                                 |    |
     * | |       1 - divides the rank by 1 + the logarithm of the document |    |
     * | |       length.                                                   |    |
     * | |                                                                 |    |
     * | |       2 - divides the rank by the document length.              |    |
     * | |                                                                 |    |
     * | |       4 - divides the rank by the mean harmonic distance        |    |
     * | |       between extents.                                          |    |
     * | |                                                                 |    |
     * | |       8 - divides the rank by the number of unique words in     |    |
     * | |       document.                                                 |    |
     * | |                                                                 |    |
     * | |       16 - divides the rank by 1 + the logarithm of the number  |    |
     * | |       of unique words in document.                              |    |
     * | |                                                                 |    |
     * | |       32 - divides the rank by itself + 1.                      |    |
     * | | If you do not specify a bitmask, the default will be a          |    |
     * | | 32 bitmask.                                                     |    |
     * | | If more than one flag bit is specified, the transformations are |    |
     * | | applied in the order listed.                                    |    |
     * | | If you do not specify fifth argument (rankingColumn) (specify   |    |
     * | | as null) then normalization masks will not be used.             |    |
     * | | Examples:                                                       |    |
     * | |       whereFullText(                                            |    |
     * | |              column or columns array,                           |    |
     * | |              'Target text for search',                          |    |
     * | |              'language name or empty string'                    |    |
     * | |              highlighting true, false or configs array,         |    |
     * | |              column or columns for ranking,                     |    |
     * | |              4                                                  |    |
     * | |       )                                                         |    |
     * | |                                                                 |    |
     * | |       whereFullText(                                            |    |
     * | |              column or columns array,                           |    |
     * | |              'Target text for search',                          |    |
     * | |              'language name or empty string'                    |    |
     * | |              highlighting true, false or configs array,         |    |
     * | |              column or columns for ranking,                     |    |
     * | |              [4, 32, 0, ...]                                    |    |
     * | |       )                                                         |    |
     * | -------------------------------------------------------------------    |
     * | --------- The below variations only for SQLite DB driver ----------    |
     * | | whereFullText('column', 'Target text for search') - performs a  |    |
     * | | full-text search of text by column.                             |    |
     * | | whereFullText(                                                  |    |
     * | |       ['column1', 'column2'],                                   |    |
     * | |       'Target text for search'                                  |    |
     * | | ) - performs full text search of text by columns.               |    |
     * | | The remaining arguments, even if you specify them, will not be  |    |
     * | | used.                                                           |    |
     * | -------------------------------------------------------------------    |
     * | ----- The below variations only for MS SQL Server DB driver -------    |
     * | | whereFullText('column', 'Target text for search') - performs a  |    |
     * | | full-text search of text by column.                             |    |
     * | | whereFullText(                                                  |    |
     * | |       ['column1'],                                              |    |
     * | |       'Target text for search'                                  |    |
     * | | ) - performs a full text search of text by column.              |    |
     * | | You cannot specify more than one column. If the first argument  |    |
     * | | is specified as an array and there are several elements in it,  |    |
     * | | then only the first element will be used and all others will be |    |
     * | | skipped.                                                        |    |
     * | | The remaining arguments, even if you specify them, will not be  |    |
     * | | used.                                                           |    |
     * | -------------------------------------------------------------------    |
     * | --------- The below variations only for Oracle DB driver ----------    |
     * | | whereFullText('column', 'Target text for search') - performs a  |    |
     * | | full-text search of text by column.                             |    |
     * | | whereFullText(                                                  |    |
     * | |       ['column1', 'column2'],                                   |    |
     * | |       'Target text for search'                                  |    |
     * | | ) - performs full text search of text by columns.               |    |
     * | | The remaining arguments, even if you specify them, will not be  |    |
     * | | used.                                                           |    |
     * | -------------------------------------------------------------------    |
     * --------------------------------------------------------------------------
     * @param string|array $column
     * @param string $value
     * @param string $searchModifier
     * @param bool|array $highlighting
     * @param string|array|null $rankingColumn
     * @param string|int|array $normalizationBitmask
     * @return $this
     * @throws Exception
     */
    public function whereFullText(string|array $column,
                                  string $value,
                                  string $searchModifier = FullTextSearchModifiers::NATURAL_LANGUAGE_MODE,
                                  bool|array $highlighting = false,
                                  string|array|null $rankingColumn = null,
                                  string|int|array $normalizationBitmask = 32
    ): self
    {
        if ($this->getDriverName() !== AvailableDbmsDrivers::MYSQL) {
            $reflectionClass = new ReflectionClass(FullTextSearchModifiers::class);

            if ($this->checkMatching($searchModifier, $reflectionClass->getConstants())) {
                $searchModifier = '';
            }
        }

        $this->whereFullTextClauseBinder(
            '',
            $column,
            $value,
            $searchModifier,
            $rankingColumn,
            $normalizationBitmask,
            $highlighting
        );

        return $this;
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause for specifying conditions in a request. Verifies json item      |
     * | value and passed value/values for equality (does the json element have |
     * | this passed value/values ?).                                           |
     * | ------------------------------ Use cases ----------------------------- |
     * | -- The below variations retrieves records that match a condition --    |
     * | | whereJsonContains('column', 'value')                            |    |
     * | | whereJsonContains('column->nestedItem1->nestedItem2', 'value')  |    |
     * | | - The below variations only for MySQL, PostgreSQL and MariaDB - |    |
     * | | | whereJsonContains('column', ['value1', 'value2'])           | |    |
     * | | | whereJsonContains(                                          | |    |
     * | | |       'column->nestedItem1->nestedItem2',                   | |    |
     * | | |       ['value1', 'value2']                                  | |    |
     * | | | )                                                           | |    |
     * | | --------------------------------------------------------------- |    |
     * | -------------------------------------------------------------------    |
     * | You can specify nested json elements via the "->" operator.            |
     * | ---------------------------------------------------------------------- |
     * | This clause only works for json type columns.                          |
     * | This clause is supported by: MySQL 8.0+, PostgreSQL 12.0+, SQL Server  |
     * | 2017+ and MariaDB.                                                     |
     * | SQLite and Oracle does not support this clause.                        |
     * | MS SQL Server does not support multiple values. As second argument you |
     * | can pass one value without array or an array with one element and this |
     * | element will be taken as the value.                                    |
     * | MySQL, PostgreSQL and MariaDB support multiple values and you can pass |
     * | array with several elements as second argument.                        |
     * --------------------------------------------------------------------------
     * @param string $column
     * @param string|array $value
     * @return $this
     * @throws Exception
     */
    public function whereJsonContains(string $column, string|array $value): self
    {
        $this->whereJsonContainsClauseBinder('', $column, $value);

        return $this;
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause for specifying conditions in a request. Verifies the length of  |
     * | a json array according to a condition.                                 |
     * | ------------------------------ Use cases ----------------------------- |
     * | -- The below variations retrieves records that match a condition --    |
     * | | whereJsonContains('column', '> or other operator' 'value')      |    |
     * | | whereJsonContains(                                              |    |
     * | |       'column->nestedItem1',                                    |    |
     * | |       '> or other operator',                                    |    |
     * | |       'value'                                                   |    |
     * | | )                                                               |    |
     * | | whereJsonContains('column', 'value') - this expression uses     |    |
     * | | the "=" operator for condition.                                 |    |
     * | | whereJsonContains('column->nestedItem1', 'value') - this        |    |
     * | | expression uses the "=" operator for condition.                 |    |
     * | |                                                                 |    |
     * | | If instead of the second argument you specify not an operator   |    |
     * | | but a value, then operator "=" will be used, and the second     |    |
     * | | argument as the value, and in this case you should skip the     |    |
     * | | third argument because if you also specify the third argument,  |    |
     * | | then the function will consider that you passed the operator as |    |
     * | | the second argument not a value and will validate this value as |    |
     * | | an operator and if it is not in the list of operators then the  |    |
     * | | function will throw an exception that you are using the wrong   |    |
     * | | operator. to avoid this, you either must specify all 3          |    |
     * | | arguments (the second is the operator, the third is the value)  |    |
     * | | or pass the first two arguments, but pass the value as the      |    |
     * | | second argument and not the operator, and in this case the      |    |
     * | | operator "=" will be used.                                      |    |
     * | -------------------------------------------------------------------    |
     * | You can specify nested json elements via the "->" operator.            |
     * | ---------------------------------------------------------------------- |
     * | This clause only works for json type columns.                          |
     * | This clause is supported by: MySQL 8.0+, PostgreSQL 12.0+, SQL Server  |
     * | 2017+ and MariaDB.                                                     |
     * | SQLite and Oracle does not support this clause.                        |
     * --------------------------------------------------------------------------
     * @param string $column
     * @param string $operator
     * @param string|int|null $value
     * @return $this
     * @throws Exception
     */
    public function whereJsonLength(string $column, string $operator, string|int|null $value = null): self
    {
        $this->whereJsonLengthClauseBinder('', $column, $operator, $value);

        return $this;
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause for sort the results of the query by a given column.            |
     * | ------------------------------ Use cases ----------------------------- |
     * | orderBy('column') - the default "asc" direction will be used if        |
     * | you do not specify a second argument and the first argument is not     |
     * | an associative array of the form column => direction.                  |
     * | orderBy('column', 'direction (desc or asc for example)')               |
     * | orderBy(                                                               |
     * |       ['column1', 'column2'],                                          |
     * |       'direction (desc or asc for example)'                            |
     * | ) - the direction specified in the second argument will be used for    |
     * | all columns listed in the first argument.                              |
     * | orderBy(                                                               |
     * |       [                                                                |
     * |              'column1' => 'direction (desc or asc for example)',       |
     * |              'column2' => 'direction (desc or asc for example)'        |
     * |       ]                                                                |
     * | ) - If you specify an associative array (column => direction) in the   |
     * | first argument then the second argument will be ignored.               |
     * | ---------------------------------------------------------------------- |
     * | You can specify directions in any case.                                |
     * --------------------------------------------------------------------------
     * @param string|array $column
     * @param string $direction
     * @return $this
     * @throws Exception
     */
    public function orderBy(string|array $column, string $direction = 'asc'): self
    {
        $this->orderByClauseBinder($column, $direction);

        return $this;
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause for sort the results of the query by a given column (in         |
     * | descending order).                                                     |
     * | ------------------------------ Use cases ----------------------------- |
     * | latest('column')                                                       |
     * | latest(['column1', 'column2'])                                         |
     * | ---------------------------------------------------------------------- |
     * | The direction "desc" (descending order) will be used.                  |
     * | You can specify an array of columns but the array cannot be            |
     * | associative if the array is associative then an exception will be      |
     * | thrown.                                                                |
     * | The same as "orderBy" with second argument "desc".                     |
     * --------------------------------------------------------------------------
     * @param string|array $column
     * @return $this
     * @throws Exception
     */
    public function latest(string|array $column): self
    {
        $this->throwExceptionIfArrayAssociative($column);

        $this->orderBy($column, 'desc');

        return $this;
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause for sort the results of the query by a given column (in         |
     * | ascending order).                                                      |
     * | ------------------------------ Use cases ----------------------------- |
     * | oldest('column')                                                       |
     * | oldest(['column1', 'column2'])                                         |
     * | ---------------------------------------------------------------------- |
     * | The direction "asc" (ascending order) will be used.                    |
     * | You can specify an array of columns but the array cannot be            |
     * | associative if the array is associative then an exception will be      |
     * | thrown.                                                                |
     * | The same as "orderBy" with second argument "asc".                      |
     * --------------------------------------------------------------------------
     * @param string|array $column
     * @return $this
     * @throws Exception
     */
    public function oldest(string|array $column): self
    {
        $this->throwExceptionIfArrayAssociative($column);

        $this->orderBy($column);

        return $this;
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause for sort the results in random order.                           |
     * | ------------------------------ Use cases ----------------------------- |
     * | inRandomOrder()                                                        |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @return $this
     * @throws Exception
     */
    public function inRandomOrder(): self
    {
        $this->orderByClauseBinder('', '', true);

        return $this;
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause for grouping rows that have the same values into total rows by  |
     * | specified column/columns.                                              |
     * | ------------------------------ Use cases ----------------------------- |
     * | groupBy('column')                                                      |
     * | groupBy('column1', 'column2', ..., 'columnN')                          |
     * | groupBy(['column'])                                                    |
     * | groupBy(['column1', 'column2', ..., 'columnN'])                        |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param string|mixed ...$columns
     * @return $this
     * @throws Exception
     */
    public function groupBy(string|array ...$columns): self
    {
        $this->groupByClauseBinder($columns);

        return $this;
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause for specifying conditions in a request.                         |
     * | ------------------------------ Use cases ----------------------------- |
     * | -- The below variations retrieves records that match a condition --    |
     * | | having('column', '=', 'value')                                  |    |
     * | | having('column', 'value') - this expression uses the "="        |    |
     * | | operator for condition.                                         |    |
     * | | having(['column', '=', 'value'])                                |    |
     * | | having(['column' => 'value']) - this expression uses the "="    |    |
     * | | operator for condition.                                         |    |
     * | | having(['column1' => 'value1', 'column2' => 'value2']) - this   |    |
     * | | expression uses the "=" operator for condition and logical      |    |
     * | | "AND" operator for combine.                                     |    |
     * | -------------------------------------------------------------------    |
     * | The conditions in "having" are checked after grouping, so they are     |
     * | specified after the "groupBy" clause. This is different from "where"   |
     * | conditions, which are applied to rows in the source table before       |
     * | grouping.                                                              |
     * --------------------------------------------------------------------------
     * @param string|array $column
     * @param string|null $operator
     * @param string|null $value
     * @return $this
     * @throws Exception
     */
    public function having(string|array $column, string|null $operator = null, string|null $value = ''): self
    {
        $this->baseConditionClauseBinder('', 'having', $column, $operator, $value);

        return $this;
    }

    // TODO having between

    /**
     * --------------------------------------------------------------------------
     * | Clause for specify the number of records to return.                    |
     * | ------------------------------ Use cases ----------------------------- |
     * | limit('5 for example or any count')                                    |
     * | limit('5 for example or any count', true) - if you pass the second     |
     * | argument as true (by default it is false) then percentage mode is      |
     * | enabled. That is, the number in the first argument will indicate not   |
     * | the number of records but the percentage of records from the total     |
     * | amount. That is, if you specified 5 in first argument and the second   |
     * | argument is true, then the query will return 5% of the records         |
     * | instead of five records. This feature only for Oracle DB driver.       |
     * | Other DB drivers do not support this feature.                          |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param int $count
     * @param bool $inPercentages
     * @return $this
     * @throws Exception
     */
    public function limit(int $count, bool $inPercentages = false): self
    {
        $this->limitClauseBinder($count, $inPercentages);

        return $this;
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause for specify the number of records to return.                    |
     * | ------------------------------ Use cases ----------------------------- |
     * | take('5 for example or any count')                                     |
     * | take('5 for example or any count', true) - if you pass the second      |
     * | argument as true (by default it is false) then percentage mode is      |
     * | enabled. That is, the number in the first argument will indicate not   |
     * | the number of records but the percentage of records from the total     |
     * | amount. That is, if you specified 5 in first argument and the second   |
     * | argument is true, then the query will return 5% of the records         |
     * | instead of five records. This feature only for Oracle DB driver.       |
     * | Other DB drivers do not support this feature.                          |
     * | ---------------------------------------------------------------------- |
     * | The same as "limit".                                                   |
     * --------------------------------------------------------------------------
     * @param int $count
     * @param bool $inPercentages
     * @return $this
     * @throws Exception
     */
    public function take(int $count, bool $inPercentages = false): self
    {
        $this->limit($count, $inPercentages);

        return $this;
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause for skipping some count of records. The offset construct        |
     * | indicates how many records to skip.                                    |
     * | ------------------------------ Use cases ----------------------------- |
     * | offset('5 for example or any count')                                   |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param int $count
     * @return $this
     */
    public function offset(int $count): self
    {
        $this->offsetClauseBinder($count);

        return $this;
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause for skipping some count of records. The skip construct          |
     * | indicates how many records to skip.                                    |
     * | ------------------------------ Use cases ----------------------------- |
     * | skip('5 for example or any count')                                     |
     * | ---------------------------------------------------------------------- |
     * | The same as "offset".                                                  |
     * --------------------------------------------------------------------------
     * @param int $count
     * @return $this
     */
    public function skip(int $count): self
    {
        $this->offset($count);

        return $this;
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause for add a some query to the current query depending on the      |
     * | another condition.                                                     |
     * | ------------------------------ Use cases ----------------------------- |
     * | when(                                                                  |
     * |       'value',                                                         |
     * |       function ($query) {                                              |
     * |              $query->...                                               |
     * |       }                                                                |
     * | ) - method executes the given closure when the first argument is true. |
     * | If the first argument is false, the closure will not be executed.      |
     * | when(                                                                  |
     * |       'value',                                                         |
     * |       function ($query) {                                              |
     * |              $query->...                                               |
     * |       },                                                               |
     * |       function ($query) {                                              |
     * |              $query->...                                               |
     * |       }                                                                |
     * | ) - You may pass another closure as the third argument to the when     |
     * | method. This closure will only execute if the first argument evaluates |
     * | as false. If the third argument is not passed or passed as null then   |
     * | the "else" case will not be executed and the code will continue        |
     * | without executing the closure for the true condition.                  |
     * | ---------------------------------------------------------------------- |
     * | The first argument must be boolean, or if another type is passed it    |
     * | will be cast to boolean according to php type casting.                 |
     * --------------------------------------------------------------------------
     * @param bool $value
     * @param callable $callback
     * @param callable|null $else
     * @return $this
     */
    public function when(string|int|bool|null $value, callable $callback, callable|null $else = null): self
    {
        $this->whenClauseBinder($value, $callback, $else);

        return $this;
    }


    public function get()
    {
        return $this->getClause();

//        return $this;
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause for inserting records.                                          |
     * | ------------------------------ Use cases ----------------------------- |
     * | insert(['column1' => 'value1', 'column2' => 'value2', ...]) - inserts  |
     * | single record.                                                         |
     * | ----- The below variations are for inserting multiple records -----    |
     * | | insert(                                                         |    |
     * | |       ['column1' => 'value1', 'column2' => 'value2', ...])      |    |
     * | |       ['column1' => 'value3', 'column2' => 'value4', ...])      |    |
     * | | )                                                               |    |
     * | | insert([                                                        |    |
     * | |       ['column1' => 'value1', 'column2' => 'value2', ...])      |    |
     * | |       ['column1' => 'value3', 'column2' => 'value4', ...])      |    |
     * | | ])                                                              |    |
     * | -------------------------------------------------------------------    |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param mixed ...$columnsWithValues
     * @throws Exception
     */
    public function insert(array ...$columnsWithValues)
    {
        $this->insertClauseBinder($columnsWithValues);

        // TODO return value
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause for inserting records with ignoring errors.                     |
     * | ------------------------------ Use cases ----------------------------- |
     * | insertOrIgnore(['column1' => 'value1', 'column2' => 'value2', ...])    |
     * | - inserts single record.                                               |
     * | ----- The below variations are for inserting multiple records -----    |
     * | | insertOrIgnore(                                                 |    |
     * | |       ['column1' => 'value1', 'column2' => 'value2', ...])      |    |
     * | |       ['column1' => 'value3', 'column2' => 'value4', ...])      |    |
     * | | )                                                               |    |
     * | | insertOrIgnore([                                                |    |
     * | |       ['column1' => 'value1', 'column2' => 'value2', ...])      |    |
     * | |       ['column1' => 'value3', 'column2' => 'value4', ...])      |    |
     * | | ])                                                              |    |
     * | -------------------------------------------------------------------    |
     * | ---------------------------------------------------------------------- |
     * | The "insertOrIgnore" method will ignore errors while inserting         |
     * | records.                                                               |
     * | Duplicate record errors will be ignored and other types of errors may  |
     * | also be ignored depending on the database engine. For example,         |
     * | "insertOrIgnore" will bypass MySQL's strict mode.                      |
     * --------------------------------------------------------------------------
     * @param mixed ...$columnsWithValues
     * @throws Exception
     */
    public function insertOrIgnore(array ...$columnsWithValues)
    {
        $this->insertClauseBinder($columnsWithValues, null, true);

        // TODO return value
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause for inserting records using a sub query to determine the data   |
     * | that should be inserted.                                               |
     * | ------------------------------ Use cases ----------------------------- |
     * | insertUsing(                                                           |
     * |       ['column1', 'column2', ...],                                     |
     * |       someBuilder->select('sameColumn1', 'sameColumn2', ...)->where... |
     * |       or other query                                                   |
     * | ) - in the first argument you can pass the columns in which the value  |
     * | should be inserted, in the second argument you specify the query which |
     * | should return the value for insertion and these values will be         |
     * | inserted into the columns listed in first argument.                    |
     * | ---------------------------------------------------------------------- |
     * | The method uses the result of the query (second argument) as a value   |
     * | and inserts them into the appropriate columns in first argument. That  |
     * | is, the method combines the columns from the first argument and the    |
     * | values from the second argument and inserts a new record.              |
     * | You can't use an associative array in the first argument, otherwise an |
     * | exception will be thrown.                                              |
     * --------------------------------------------------------------------------
     * @param array $columns
     * @param $query
     * @throws Exception
     */
    public function insertUsing(array $columns, $query)
    {
        $this->insertClauseBinder($columns, $query);

        // TODO return value
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause for inserting non-existent records and updating existing        |
     * | records with new values.                                               |
     * | ------------------------------ Use cases ----------------------------- |
     * | upsert(                                                                |
     * |       ['column1' => 'value1', 'column2' => 'value2']                   |
     * | ) - if the inserted row results in a duplicate value in at least one   |
     * | of the listed columns in the unique or primary key index, the old row  |
     * | will be update, if not, the record/records will be inserted. That is,  |
     * | if in the listed columns have a column with an unique or primary key   |
     * | indexes and there is a collision when inserting, then the data is      |
     * | updated, if not, then an insertion will occur.                         |
     * |                                                                        |
     * | upsert(                                                                |
     * |       ['column1' => 'value1', 'column2' => 'value2']                   |
     * |       ['column1', 'column2']                                           |
     * | ) - same as first variation with the difference that the columns that  |
     * | need to be updated are indicated, in this case, in the event of a      |
     * | collision, not all columns are updated, but only those specified.      |
     * | The list columns(s) of the second argument must also be present in the |
     * | first argument so that the method knows what value to use and takes    |
     * | the value from the first argument.                                     |
     * |                                                                        |
     * | upsert(                                                                |
     * |       ['column1' => 'value1', 'column2' => 'value2']                   |
     * |       'column1'                                                        |
     * | ) - same as above variation, but with the difference that instead of   |
     * | array of columns, passed single column for updating.                   |
     * |                                                                        |
     * | -- The below variations for all drivers except MySQL and MariaDB --    |
     * | | upsert(..., ..., 'column')                                      |    |
     * | |                                                                 |    |
     * | | upsert(..., ..., ['column1', 'column2'])                        |    |
     * | |                                                                 |    |
     * | | You can pass a third argument, this will be the column/columns  |    |
     * | | by which it will be identified record/records. You can pass     |    |
     * | | single column or array of columns. The column/columns must      |    |
     * | | either be unique or be a primary key.                           |    |
     * | -------------------------------------------------------------------    |
     * |                                                                        |
     * | For all variations you may not pass the second argument in which case  |
     * | all columns from the first argument will be updated and if you use the |
     * | third argument and do not want to specify the columns in the second    |
     * | argument you can pass the second argument as null.                     |
     * |                                                                        |
     * | upsert(                                                                |
     * |       [                                                                |
     * |              ['column1' => 'value1', 'column2' => 'value2']            |
     * |              ['column1' => 'value3', 'column2' => 'value4']            |
     * |       ],                                                               |
     * |       ...,                                                             |
     * |       ...                                                              |
     * | ) - you can also pass multiple records in the first argument, all the  |
     * | options listed will work for multiple records as well.                 |
     * | ---------------------------------------------------------------------- |
     * | First argument must be an associative array (column => value).         |
     * | The third argument will be ignored if you use MySQL or MariaDB DB      |
     * | drivers.                                                               |
     * --------------------------------------------------------------------------
     * @param array $values
     * @param string|array|null $update
     * @param string|array|null $uniqueBy
     * @throws Exception
     */
    public function upsert(array $values, string|array|null $update = null, string|array|null $uniqueBy = null)
    {
        $this->insertClauseBinder($values, null, false, true, $uniqueBy, $update);

        // TODO return value
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause for update a record.                                            |
     * | ------------------------------ Use cases ----------------------------- |
     * | update(['column1' => 'value1', 'column2' => 'value2', ...])            |
     * |                                                                        |
     * | update(['column1->nestedItem1' => 'value1', ...]) - you can also       |
     * |                                                                        |
     * | update the json columns. You can specify nested json elements via the  |
     * | "->" operator. Oracle DB driver doesn't support json update and you    |
     * | can't use this variation.                                              |
     * | ---------------------------------------------------------------------- |
     * | Argument must be an associative array (column => value).               |
     * --------------------------------------------------------------------------
     * @param array $columnsWithValues
     * @throws Exception
     */
    public function update(array $columnsWithValues)
    {
        $this->updateClauseBinder($columnsWithValues);

        // TODO return value
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause for updating an existing record or inserting a non-existent     |
     * | record.                                                                |
     * | ------------------------------ Use cases ----------------------------- |
     * | updateOrInsert(                                                        |
     * |       ['column1' => 'value1', ...], ['column2' => 'value2', ...]       |
     * | )                                                                      |
     * |                                                                        |
     * | updateOrInsert(                                                        |
     * |       ['column1->nestedItem1' => 'value1', ...],                       |
     * |       ['column2->nestedItem2' => 'value2', ...]                        |
     * | ) - You can also work with json in the first and second arguments. But |
     * | keep in mind since the method under the hood works with methods        |
     * | "where", "insert" and "update " that the "where" and "update" methods  |
     * | support working with json, the "insert" method does not support such   |
     * | a record since it creates a record from scratch and if you specify     |
     * | this in the first argument, the method can check nested json and if    |
     * | the record exists, it can update the nested json specified in the      |
     * | second argument in this way, but if there is no record and the method  |
     * | will insert data from the first and second argument by merging them    |
     * | together, then the nested json record will be regarded as a column     |
     * | name and not as nested json and the method will try to insert the      |
     * | record in this way as insert does not support working with nested      |
     * | json.                                                                  |
     * | You can specify nested json elements via the "->" operator. Oracle DB  |
     * | driver doesn't support json update and you can't use this variation.   |
     * | All restrictions and rules that apply to methods "where", "insert" and |
     * | "update" also apply to arguments since the method under the hood uses  |
     * | these methods and passes arguments to them.                            |
     * | ---------------------------------------------------------------------- |
     * | The arguments must be associative arrays (column => value) since they  |
     * | specify columns of values.                                             |
     * |  If the method does not find a record that matches the conditions      |
     * | passed in the first argument, then the method creates a record based   |
     * | on the first and second arguments (merges them together and creates a  |
     * | record), and if such a record exists, then the method updates the      |
     * | record in accordance with the second arguments (updates the columns    |
     * | and values that are specified in the second argument).                 |
     * --------------------------------------------------------------------------
     * @param array $condition
     * @param array $forUpdate
     * @return bool|void
     * @throws Exception
     */
    public function updateOrInsert(array $condition, array $forUpdate)
    {
        if (!$this->where($condition)->exists()) {
            return $this->insert(array_merge($condition, $forUpdate));
        }

        return (bool)$this->where($condition)->update($forUpdate);
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause for join tables.                                                |
     * | ------------------------------ Use cases ----------------------------- |
     * | join('tableForJoin', 'column1', '= or other operator', 'column2')      |
     * |                                                                        |
     * | join(                                                                  |
     * |       'tableForJoin',                                                  |
     * |       'column1',                                                       |
     * |       '= or other operator',                                           |
     * |       'column2',                                                       |
     * |       'inner or other join type'                                       |
     * | ) - you can pass the join type as the last argument (you can also use  |
     * | separate methods for this).                                            |
     * |                                                                        |
     * | For all variations instead of columns you can pass the column along    |
     * | with the table (table.column). In this case, if tables in a place with |
     * | columns are passed in the second and fourth arguments, then these      |
     * | tables will be used, but if not passed, then the current table will be |
     * | used for the second argument and the table specified in the first      |
     * | argument will be used for the fourth argument, but note that if you    |
     * | specify a table only for the second or only for the fourth argument,   |
     * | then it will be ignored and the method will be treated as the name of  |
     * | the column and also for the second the current table will be used,     |
     * | and for the fourth, the table passed in the first argument. If you     |
     * | want to specify tables in a place with columns then you must pass      |
     * | tables for both arguments (for both the second and fourth). By saying  |
     * | “use table for column” we mean the column will be considered a column  |
     * | of this table.                                                         |
     * |                                                                        |
     * | The first argument passed to the join method is the name of the table  |
     * | you need to join to, while the remaining arguments specify the column  |
     * | constraints for the join.                                              |
     * |                                                                        |
     * | If you don't pass the last argument then the default is "inner" but    |
     * | you can also use "leftOuter", "rightOuter", "fullOuter" and "cross"    |
     * | joins.                                                                 |
     * | ---------------------------------------------------------------------- |
     * | The method joins the current table with the table specified in the     |
     * | first argument.                                                        |
     * --------------------------------------------------------------------------
     * @param string|array $table
     * @param string $firstColumn
     * @param string $operator
     * @param string $secondColumn
     * @param string $joinType
     * @return $this
     */
    public function join(string|array $table,
                         string $firstColumn,
                         string $operator,
                         string $secondColumn,
                         string $joinType = 'inner'): self
    {
        $this->joinClauseBinder($table, $firstColumn, $operator, $secondColumn, $joinType);

        return $this;
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause for join tables.                                                |
     * | ------------------------------ Use cases ----------------------------- |
     * | leftJoin('tableForJoin', 'column1', '= or other operator', 'column2')  |
     * |                                                                        |
     * | Instead of columns you can pass the column along with the table        |
     * | (table.column). In this case, if tables in a place with columns are    |
     * | passed in the second and fourth arguments, then these tables will be   |
     * | used, but if not passed, then the current table will be used for the   |
     * | second argument and the table specified in the first argument will be  |
     * | used for the fourth argument, but note that if you specify a table     |
     * | only for the second or only for the fourth argument, then it will be   |
     * | ignored and the method will be treated as the name of the column and   |
     * | also for the second the current table will be used, and for the        |
     * | fourth, the table passed in the first argument. If you want to specify |
     * | tables in a place with columns then you must pass tables for both      |
     * | arguments (for both the second and fourth). By saying “use table for   |
     * | column” we mean the column will be considered a column of this table.  |
     * |                                                                        |
     * | The first argument passed to the join method is the name of the table  |
     * | you need to join to, while the remaining arguments specify the column  |
     * | constraints for the join.                                              |
     * | ---------------------------------------------------------------------- |
     * | The method joins the current table with the table specified in the     |
     * | first argument.                                                        |
     * | The same as "join" with the last argument passed as "leftOuter".       |
     * --------------------------------------------------------------------------
     * @param string|array $table
     * @param string $firstColumn
     * @param string $operator
     * @param string $secondColumn
     * @return $this
     */
    public function leftJoin(string|array $table, string $firstColumn, string $operator, string $secondColumn): self
    {
        $this->join($table, $firstColumn, $operator, $secondColumn, 'leftOuter');

        return $this;
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause for join tables.                                                |
     * | ------------------------------ Use cases ----------------------------- |
     * | rightJoin('tableForJoin', 'column1', '= or other operator', 'column2') |
     * |                                                                        |
     * | Instead of columns you can pass the column along with the table        |
     * | (table.column). In this case, if tables in a place with columns are    |
     * | passed in the second and fourth arguments, then these tables will be   |
     * | used, but if not passed, then the current table will be used for the   |
     * | second argument and the table specified in the first argument will be  |
     * | used for the fourth argument, but note that if you specify a table     |
     * | only for the second or only for the fourth argument, then it will be   |
     * | ignored and the method will be treated as the name of the column and   |
     * | also for the second the current table will be used, and for the        |
     * | fourth, the table passed in the first argument. If you want to specify |
     * | tables in a place with columns then you must pass tables for both      |
     * | arguments (for both the second and fourth). By saying “use table for   |
     * | column” we mean the column will be considered a column of this table.  |
     * |                                                                        |
     * | The first argument passed to the join method is the name of the table  |
     * | you need to join to, while the remaining arguments specify the column  |
     * | constraints for the join.                                              |
     * | ---------------------------------------------------------------------- |
     * | The method joins the current table with the table specified in the     |
     * | first argument.                                                        |
     * | The same as "join" with the last argument passed as "rightOuter".      |
     * --------------------------------------------------------------------------
     * @param string|array $table
     * @param string $firstColumn
     * @param string $operator
     * @param string $secondColumn
     * @return $this
     */
    public function rightJoin(string|array $table, string $firstColumn, string $operator, string $secondColumn): self
    {
        $this->join($table, $firstColumn, $operator, $secondColumn, 'rightOuter');

        return $this;
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause for join tables.                                                |
     * | ------------------------------ Use cases ----------------------------- |
     * | fullJoin('tableForJoin', 'column1', '= or other operator', 'column2')  |
     * |                                                                        |
     * | Instead of columns you can pass the column along with the table        |
     * | (table.column). In this case, if tables in a place with columns are    |
     * | passed in the second and fourth arguments, then these tables will be   |
     * | used, but if not passed, then the current table will be used for the   |
     * | second argument and the table specified in the first argument will be  |
     * | used for the fourth argument, but note that if you specify a table     |
     * | only for the second or only for the fourth argument, then it will be   |
     * | ignored and the method will be treated as the name of the column and   |
     * | also for the second the current table will be used, and for the        |
     * | fourth, the table passed in the first argument. If you want to specify |
     * | tables in a place with columns then you must pass tables for both      |
     * | arguments (for both the second and fourth). By saying “use table for   |
     * | column” we mean the column will be considered a column of this table.  |
     * |                                                                        |
     * | The first argument passed to the join method is the name of the table  |
     * | you need to join to, while the remaining arguments specify the column  |
     * | constraints for the join.                                              |
     * | ---------------------------------------------------------------------- |
     * | The method joins the current table with the table specified in the     |
     * | first argument.                                                        |
     * | The same as "join" with the last argument passed as "fullOuter".       |
     * --------------------------------------------------------------------------
     * @param string|array $table
     * @param string $firstColumn
     * @param string $operator
     * @param string $secondColumn
     * @return $this
     */
    public function fullJoin(string|array $table, string $firstColumn, string $operator, string $secondColumn): self
    {
        $this->join($table, $firstColumn, $operator, $secondColumn, 'fullOuter');

        return $this;
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause for join tables.                                                |
     * | ------------------------------ Use cases ----------------------------- |
     * | crossJoin('tableForJoin', 'column1', '= or other operator', 'column2') |
     * |                                                                        |
     * | Instead of columns you can pass the column along with the table        |
     * | (table.column). In this case, if tables in a place with columns are    |
     * | passed in the second and fourth arguments, then these tables will be   |
     * | used, but if not passed, then the current table will be used for the   |
     * | second argument and the table specified in the first argument will be  |
     * | used for the fourth argument, but note that if you specify a table     |
     * | only for the second or only for the fourth argument, then it will be   |
     * | ignored and the method will be treated as the name of the column and   |
     * | also for the second the current table will be used, and for the        |
     * | fourth, the table passed in the first argument. If you want to specify |
     * | tables in a place with columns then you must pass tables for both      |
     * | arguments (for both the second and fourth). By saying “use table for   |
     * | column” we mean the column will be considered a column of this table.  |
     * |                                                                        |
     * | The first argument passed to the join method is the name of the table  |
     * | you need to join to, while the remaining arguments specify the column  |
     * | constraints for the join.                                              |
     * | ---------------------------------------------------------------------- |
     * | The method joins the current table with the table specified in the     |
     * | first argument.                                                        |
     * | The same as "join" with the last argument passed as "cross".           |
     * --------------------------------------------------------------------------
     * @param string|array $table
     * @param string $firstColumn
     * @param string $operator
     * @param string $secondColumn
     * @return $this
     */
    public function crossJoin(string|array $table, string $firstColumn, string $operator, string $secondColumn): self
    {
        $this->join($table, $firstColumn, $operator, $secondColumn, 'cross');

        return $this;
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause for union the queries.                                          |
     * | ------------------------------ Use cases ----------------------------- |
     * | union($query->...)                                                     |
     * |                                                                        |
     * | union($query->..., true) - if both sets being merged contain identical |
     * | row values, then the duplicate rows are removed during the merge. If   |
     * | you need to save everything when merging, including duplicate rows,    |
     * | then you need to pass the "all" argument as true, in this case, all    |
     * | data will be saved, including duplicate rows. If you dont pass the     |
     * | last argument as true, by default false will be used and only one copy |
     * | will be saved from duplicates.                                         |
     * | ---------------------------------------------------------------------- |
     * | The function "union" allows you to combine two samples of the same     |
     * | type. These selections can be from different tables or from the same   |
     * | table.                                                                 |
     * | If one selection has more columns than another, they cannot be merged. |
     * | The argument must be a query (a query builder object).                 |
     * --------------------------------------------------------------------------
     * @param $query
     * @param bool $all
     * @return $this
     * @throws Exception
     */
    public function union($query, bool $all = false): self
    {
        $this->unionClauseBinder($query, $all);

        return $this;
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause for union the queries.                                          |
     * | ------------------------------ Use cases ----------------------------- |
     * | unionAll($query->...)                                                  |
     * | ---------------------------------------------------------------------- |
     * | The function "unionAll" allows you to combine two samples of the same  |
     * | type. These selections can be from different tables or from the same   |
     * | table.                                                                 |
     * | The function will save all data, including duplicate rows.             |
     * | If one selection has more columns than another, they cannot be merged. |
     * | The argument must be a query (a query builder object).                 |
     * | The same as "union" with last argument passed as true.                 |
     * --------------------------------------------------------------------------
     * @param $query
     * @return $this
     * @throws Exception
     */
    public function unionAll($query): self
    {
        $this->unionClauseBinder($query, true);

        return $this;
    }

    /**
     * --------------------------------------------------------------------------
     * | In database management, the unary operation a mathematical operation   |
     * | on one operand and returning one result, that is, an operation with a  |
     * | single input and a single output.                                      |
     * --------------------------------------------------------------------------
     */

    /**
     * --------------------------------------------------------------------------
     * | Clause for incrementing the value of a column.                         |
     * | ------------------------------ Use cases ----------------------------- |
     * | increment('column1')                                                   |
     * |                                                                        |
     * | increment('column1', '2 or other amount') - by passing a number as the |
     * | second argument you can increment the value by that number, if you     |
     * | don't pass a number then by default the value is incremented by 1.     |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param string $column
     * @param int|float|string $amount
     */
    public function increment(string $column, int|float|string $amount = 1)
    {
        $this->unaryOperatorsClauseBinder($column, $amount);

        // TODO return update value
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause for incrementing the values of a columns.                       |
     * | ------------------------------ Use cases ----------------------------- |
     * | incrementEach(['column1' => '1 or other amount', 'column2' => 2])      |
     * | ---------------------------------------------------------------------- |
     * | You must pass an associative array (column => amount) as an argument.  |
     * --------------------------------------------------------------------------
     * @param array $columns
     */
    public function incrementEach(array $columns)
    {
        $this->unaryOperatorsClauseBinder($columns);

        // TODO return update value
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause for decrementing the value of a column.                         |
     * | ------------------------------ Use cases ----------------------------- |
     * | decrement('column1')                                                   |
     * |                                                                        |
     * | decrement('column1', '2 or other amount') - by passing a number as the |
     * | second argument you can decrement the value by that number, if you     |
     * | don't pass a number then by default the value is decremented by 1.     |
     * | ---------------------------------------------------------------------- |
     * --------------------------------------------------------------------------
     * @param string $column
     * @param int|float|string $amount
     */
    public function decrement(string $column, int|float|string $amount = 1)
    {
        $this->unaryOperatorsClauseBinder($column, $amount, '-');

        // TODO return update value
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause for decrementing the values of a columns.                       |
     * | ------------------------------ Use cases ----------------------------- |
     * | decrementEach(['column1' => '1 or other amount', 'column2' => 2])      |
     * | ---------------------------------------------------------------------- |
     * | You must pass an associative array (column => amount) as an argument.  |
     * --------------------------------------------------------------------------
     * @param array $columns
     */
    public function decrementEach(array $columns)
    {
        $this->unaryOperatorsClauseBinder($columns, null, '-');

        // TODO return update value
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause for deleting the record.                                        |
     * | ------------------------------ Use cases ----------------------------- |
     * | delete() - you can call the "delete" method without arguments if you   |
     * | previously specified a specific record(s) in the request in some way   |
     * | (using the "where" function), in this case the record(s) that matches  |
     * | the previously specified conditions will be deleted. If you have not   |
     * | specified any record(s) using “where” function, all record(s) will be  |
     * | deleted.                                                               |
     * |                                                                        |
     * | delete('value1') - the record(s) with the specified value of the "id"  |
     * | column will be deleted, but other “where” will also be taken into      |
     * | account if you also specified them in the request, since when deleting |
     * | all conditions are combined and the record(s) is deleted in accordance |
     * | with them.                                                             |
     * |                                                                        |
     * | delete('value1', 'column1') - you can also not specify the record(s)   |
     * | separately in the query, but immediately call “delete” and specify a   |
     * | separate record(s) in it. You must specify the value as the first      |
     * | argument and the column and record(s) as the second argument. Will be  |
     * | deleted in accordance with this condition. I you do not specify the    |
     * | second argument, the column "id" will be used by default. If you used  |
     * | other "where" functions then they will also be taken into account when |
     * | deleting a record(s) and the condition that you specified in the       |
     * | "delete" method will be added to the rest "where".                     |
     * | ---------------------------------------------------------------------- |
     * | The method can also delete several records if they meet the            |
     * | conditions, that is, in addition to one record, it can also delete a   |
     * | set of records.                                                        |
     * --------------------------------------------------------------------------
     * @param string|null $uniqueValue
     * @param string $uniqueColumn
     */
    public function delete(string|null $uniqueValue = null, string $uniqueColumn = 'id')
    {
        $this->deleteClauseBinder($uniqueValue, $uniqueColumn);

        // TODO return delete response value
    }

    /**
     * --------------------------------------------------------------------------
     * | Clause for truncate the entire table.                                  |
     * | ------------------------------ Use cases ----------------------------- |
     * | truncate()                                                             |
     * | ---------------------------------------------------------------------- |
     * | The method will remove all records from the table and reset the        |
     * | auto-incrementing identifier to zero.                                  |
     * --------------------------------------------------------------------------
     */
    public function truncate()
    {
        return $this->truncateClauseBinder();
    }

    /**
     * @return $this
     */
    public function sharedLock(): self
    {
        $this->lockClauseBinder();

        return $this;
    }

    /**
     * @return $this
     */
    public function lockForUpdate(): self
    {
        $this->lockClauseBinder(false);

        return $this;
    }
}