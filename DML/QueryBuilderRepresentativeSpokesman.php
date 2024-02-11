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

    // TODO: [implement] implement function
    public function find(int $id)
    {
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
     * @param string $keyColumn
     * @param string ...$valueColumn
     * @return $this
     * @throws Exception
     */
    public function jsonObjectAgg(string $keyColumn, string ...$valueColumn): self
    {
        $driver = $this->getDriver();

        $aggregateFunction = match ($this->getDriver()) {
            AvailableDbmsDrivers::POSTGRESQL => 'JSON_OBJECT_AGG',
            AvailableDbmsDrivers::SQLITE,
            AvailableDbmsDrivers::MS_SQL_SERVER => 'JSON_OBJECT',
            default => 'JSON_OBJECTAGG'
        };

        // If the Microsoft SQL Server driver is used, the keyColumn argument
        // is also treated as an element of the valueColumn argument.
        if ($driver === AvailableDbmsDrivers::MS_SQL_SERVER) {
            array_unshift($valueColumn, $keyColumn);

            foreach ($valueColumn as $key => $value) {
                $valueColumn[$key] = $this->wrapStringInPita($value) . ':' . $value;
            }

            $this->aggregateFunctionsClauseBinder($aggregateFunction, $valueColumn, false, false);
        } else {
            if (count($valueColumn) > 1) {
                throw new Exception(
                    'When using all drivers except Microsoft SQL Server, the second argument to the 
                    jsonObjectAgg method must be provided with no more than one element.'
                );
            }

            $valueColumn = $valueColumn[array_key_first($valueColumn)];

            $this->aggregateFunctionsClauseBinder($aggregateFunction, [$keyColumn, $valueColumn]);
        }

        return $this;
    }

    /**
     * @param string $column
     * @param bool $biased
     * @return $this
     * @throws Exception
     */
    /*
     * STDEV - is used when the group of numbers being evaluated are only a partial sampling of the whole
     * population. The denominator for dividing the sum of squared deviations is N-1, where N is the number of
     * observations ( a count of items in the data set ). Technically, subtracting the 1 is referred to
     * as "non-biased."
     * STDEVP is used when the group of numbers being evaluated is complete - it's the entire population of
     * values. In this case, the 1 is NOT subtracted and the denominator for dividing the sum of squared
     * deviations is simply N itself, the number of observations ( a count of items in the data set ).
     * Technically, this is referred to as "biased." Remembering that the P in STDEVP stands for "population"
     * may be helpful. Since the data set is not a mere sample, but constituted of ALL the actual values,
     * this standard deviation function can return a more precise result.
     */
    public function stdDev(string $column, bool $biased = false): self
    {
        $driver = $this->getDriver();

        if ($driver === AvailableDbmsDrivers::SQLITE) {
            throw new Exception(
                'Sqlite driver does not support this feature.'
            );
        } elseif ($driver === AvailableDbmsDrivers::MSSQLSERVER) {
            $aggregateFunction = 'STDEVP';
        } else {
            $aggregateFunction = 'STDDEV_POP';
        }

        $this->aggregateFunctionsClauseBinder($aggregateFunction, $column);

        return $this;
    }

    /**
     * @param string $column
     * @return $this
     * @throws Exception
     */
    public function stdDevSamp(string $column): self
    {
        $driver = $this->getDriver();

        if ($driver === AvailableDbmsDrivers::SQLITE) {
            throw new Exception(
                'Sqlite driver does not support this feature.'
            );
        } elseif ($driver === AvailableDbmsDrivers::MSSQLSERVER) {
            $aggregateFunction = 'STDEV';
        } else {
            $aggregateFunction = 'STDDEV_SAMP';
        }

        $this->aggregateFunctionsClauseBinder($aggregateFunction, $column);

        return $this;
    }

    /**
     * @param string $column
     * @return $this
     * @throws Exception
     */
    public function varPop(string $column): self
    {
        $driver = $this->getDriver();

        if ($driver === AvailableDbmsDrivers::SQLITE) {
            throw new Exception(
                'Sqlite driver does not support this feature.'
            );
        } elseif ($driver === AvailableDbmsDrivers::MSSQLSERVER) {
            $aggregateFunction = 'VARP';
        } else {
            $aggregateFunction = 'VAR_POP';
        }

        $this->aggregateFunctionsClauseBinder($aggregateFunction, $column);

        return $this;
    }

    /**
     * @param string $column
     * @return $this
     * @throws Exception
     */
    public function varSamp(string $column): self
    {
        $driver = $this->getDriver();

        if ($driver === AvailableDbmsDrivers::SQLITE) {
            throw new Exception(
                'Sqlite driver does not support this feature.'
            );
        } elseif ($driver === AvailableDbmsDrivers::MSSQLSERVER) {
            $aggregateFunction = 'VAR';
        } else {
            $aggregateFunction = 'VAR_SAMP';
        }

        $this->aggregateFunctionsClauseBinder($aggregateFunction, $column);

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
     * | A predictive function that checks for the presence of a record         |
     * | matching the requirements in a sub query.                              |
     * | ------------------------------ Use cases ----------------------------- |
     * | exists() - checks whether a record exists that matches the             |
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
     * | A predictive function that checks for the absence of a matching record |
     * | in a sub query.                                                        |
     * | ------------------------------ Use cases ----------------------------- |
     * | doesntExists() - checks whether the corresponding record is missing    |
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
     * @param string $table
     * @return $this
     */
    public function from(string $table): self
    {
        $this->fromClauseBinder($table);

        return $this;
    }

    /**
     * @param string $table
     * @return $this
     */
    public function table(string $table): self
    {
        $this->fromClauseBinder($table);

        return $this;
    }

    /**
     * @param string|array|callable $column
     * @param string|null $operator
     * @param string|null $value
     * @return $this
     * @throws Exception
     */
    public function where(string|array|callable $column, string|null $operator = null, string|null $value = ''): self
    {
        $this->baseConditionClauseBinder('', 'where', $column, $operator, $value);

        return $this;
    }

    /**
     * @param string|callable $column
     * @param array|string|int|float $range
     * @param string|int|float $endOfRange
     * @return $this
     * @throws Exception
     */
    public function whereBetween(string|callable $column, array|string|int|float $range = '', string|int|float $endOfRange = ''): self
    {
        $this->whereBetweenClauseBinder('', $column, $range, $endOfRange);

        return $this;
    }

    /**
     * @param string|callable $column
     * @param array|string|int|float $range
     * @param string|int|float $endOfRange
     * @return $this
     * @throws Exception
     */
    public function whereBetweenColumns(string|callable $column, array|string|int|float $range = '', string|int|float $endOfRange = ''): self
    {
        $this->whereBetweenClauseBinder('', $column, $range, $endOfRange, false, true);

        return $this;
    }

    /**
     * @param string|callable $column
     * @param array $setOfSupposedVariables
     * @return $this
     * @throws Exception
     */
    public function whereIn(string|callable $column, array $setOfSupposedVariables = []): self
    {
        $this->whereInClauseBinder('', $column, $setOfSupposedVariables);

        return $this;
    }

    /**
     * @param string|callable $column
     * @return $this
     */
    public function whereNull(string|callable $column): self
    {
        $this->whereNullClauseBinder('', $column, false);

        return $this;
    }

    /**
     * @param callable $callback
     * @return $this
     */
    public function whereExists(callable $callback): self
    {
        $this->whereExistsClauseBinder('', $callback);

        return $this;
    }

    /**
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

    // MySql
    /*
     * -- In natural language mode --
     * Полнотекстовый поиск в режиме поиска на естественном языке
     * Режим поиска на естественном языке, как отмечалось выше, включен по умолчанию или
     * при указании модификатора IN NATURAL LANGUAGE MODE . В этом режиме выполняется поиск на естественном
     * языке по заданному текстовому набору (один или несколько столбцов). Базовый формат запроса
     * полнотекстового поиска в MySQL должен быть примерно таким:
     */

    /*
     * -- With query expansion mode --
     * Полнотекстовый поиск в режиме расширения запроса
     * Полнотекстовый поиск также поддерживает режим расширения запроса. Такой режим поиска часто используется, когда
     * пользователь полагается на подразумеваемые знания — например, пользователь может искать «СУБД», надеясь
     * увидеть в результатах поиска как «MongoDB», так и «MySQL». Причина, по которой пользователь может полагаться
     * на некоторые подразумеваемые знания при использовании такого режима поиска, довольно проста —
     * полнотекстовый поиск с режимом расширения запроса работает, выполняя поиск дважды: вторая поисковая фраза —
     * это первая поисковая фраза. объединены с несколькими наиболее релевантными записями из первого поиска.
     * Это означает, что, например, если при первом поиске одна из строк будет содержать слово «СУБД» и слово
     * «MySQL», то при втором поиске будут найдены записи, содержащие слово «MySQL»,
     * даже если они не содержат содержать «СУБД».
     */

    /*
     * В РЕЖИМЕ ЕСТЕСТВЕННОГО ЯЗЫКА ... ваш поисковый запрос
     * будет рассматриваться как естественный язык (человеческий язык). Так что здесь нет специальных символов,
     * кроме " (двойная кавычка). Все слова в вашем списке стоп-слов будут исключены при поиске!
     *
     * В БУЛЕВОМ РЕЖИМЕ ... операторы могут быть добавлены к вашему поисковому запросу. Это означает,
     * что вы можете указать дополнительные пожелания относительно вашего поиска. Конечно,
     * также применяется правило списка стоп-слов, означающее, что они будут исключены из вашего поиска.
     *
     * С РАСШИРЕНИЕМ ЗАПРОСА (или В РЕЖИМЕ ЕСТЕСТВЕННОГО ЯЗЫКА С РАСШИРЕНИЕМ ЗАПРОСА) ...
     * так как эта фамилия подразумевает расширение до В ЕСТЕСТВЕННОМ РЕЖИМЕ. Таким образом, это в основном
     * то же самое, что и этот первый режим, упомянутый выше, за исключением этой функции: наиболее
     * релевантные слова, найденные с вашим начальным поисковым запросом, добавляются к вашему
     * начальному поисковому запросу, и выполняется окончательный поиск. Запрос возвращает более
     * широкий результат с вашим поисковым запросом и тем, что может быть интересным,
     * если вы согласны с таким определением интересного.
     */

    //  with query expansion mode -> Слепое расширение запроса (также известное как автоматическая обратная
    // связь по релевантности).

    // whereFullText('text', 'Hello world') -> WHERE MATCH (`text`) AGAINST ("Hello world" IN NATURAL LANGUAGE MODE)
    // whereFullText(['title', 'text'], 'Hello world') -> WHERE MATCH (`title`, `text`) AGAINST ("Hello world" IN NATURAL LANGUAGE MODE)


    // MySql
    // whereFullText('text', 'Hello world', FullTextSearchModifiers::NATURAL_LANGUAGE_MODE) -> in natural language mode
    // whereFullText(['text'], ['Hello world'], FullTextSearchModifiers::NATURAL_LANGUAGE_MODE) -> in natural language mode
    // whereFullText(['text'], ['Hello world'], FullTextSearchModifiers::WITH_QUERY_EXPANSION) -> with query expansion mode
    // whereFullText(['text'], ['Hello world'], FullTextSearchModifiers::BOOLEAN_MODE) -> in boolean mode

    // PostgreSql
    // whereFullText('text', 'Hello world', 'language')
    // -> SELECT `*` FROM `users` WHERE to_tsvector('language', `text`) @@ to_tsquery('language', "Hello world")
    // whereFullText(['text'], ['Hello world'], 'language')
    // -> SELECT `*` FROM `users` WHERE to_tsvector('language', `text`) @@ to_tsquery('language', "Hello world")

    /*
     * 0 (по умолчанию): длина документа не учитывается
     * 1: ранг документа делится на 1 + логарифм длины документа
     * 2: ранг документа делится на его длину
     * 4: ранг документа делится на среднее гармоническое расстояние между блоками (это реализовано только в ts_rank_cd)
     * 8: ранг документа делится на число уникальных слов в документе
     * 16: ранг документа делится на 1 + логарифм числа уникальных слов в документе
     * 32: ранг делится своё же значение + 1
     */
    /*
     * -- PostgreSql --
     * ->whereFullText('title', 'Some text');
     * ->whereFullText(['title', 'description'], 'Some text');
     * ->whereFullText('title', 'Some text', 'english');
     * ->whereFullText('title', 'Some text', 'english', true);
     * ->whereFullText('title', 'Some text', 'english', true, 'title');
     * ->whereFullText(['title', 'description'], 'Some text', '', false, ['title', 'description]);
     * ->whereFullText(['title', 'description'], 'Some text', '', false, '', 32);
     * ->whereFullText(['title', 'description'], 'Some text', '', false, '', [32, 2]);
     * ->whereFullText(['title', 'description'], 'Some text', '', false, '', [32, '2']);
     * ->whereFullText(['title', 'description'], 'Some text', 'english', ['tag' => 'mark', 'MaxWords' => 10]);
     *
     * -- MySql --
     * ->whereFullText('title', 'Some text');
     * ->whereFullText(['title', 'description'], 'Some text');
     * ->whereFullText(['title', 'description'], 'Some text', FullTextSearchModifiers::BOOLEAN_MODE);
     */
    /**
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
        if ($this->getDriver() !== AvailableDbmsDrivers::MYSQL) {
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

    public function whereJsonContains(string $column, string|array $value): self
    {
        $this->whereJsonContainsClauseBinder('', $column, $value);

        return $this;
    }

    public function whereJsonLength(string $column, string $operator, string|int|null $value = null): self
    {
        $this->whereJsonLengthClauseBinder('', $column, $operator, $value);

        return $this;
    }

    /*
     * ->orderBy('a', 'DESC')
     * ->orderBy('a', 'desc')
     * ->orderBy('a', 'asc')
     * ->orderBy(['a', 'b'], 'asc')
     * ->orderBy(['a' => 'asc', 'b' => 'desc'])
     */
    /**
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
     * @param string|array $column
     * @return $this
     * @throws Exception
     */
    public function latest(string|array $column): self
    {
        $this->orderBy($column, 'desc');

        return $this;
    }

    /**
     * @param string|array $column
     * @return $this
     * @throws Exception
     */
    public function oldest(string|array $column): self
    {
        $this->orderBy($column);

        return $this;
    }

    /**
     * @return $this
     * @throws Exception
     */
    public function inRandomOrder(): self
    {
        $this->orderByClauseBinder('', '', true);

        return $this;
    }

    /**
     * @param string|mixed ...$columns
     * @return $this
     */
    public function groupBy(string|array ...$columns): self
    {
        $this->groupByClauseBinder($columns);

        return $this;
    }

    /**
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
     * @param int $count
     * @return $this
     */
    public function offset(int $count): self
    {
        $this->offsetClauseBinder($count);

        return $this;
    }

    /**
     * @param int $count
     * @return $this
     */
    public function skip(int $count): self
    {
        $this->offset($count);

        return $this;
    }

    /**
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
     * @param bool $value
     * @param callable $callback
     * @param callable|null $else
     * @return $this
     */
    public function when(bool $value, callable $callback, callable|null $else = null): self
    {
        $this->whenClauseBinder($value, $callback, $else);

        return $this;
    }


    public function get()
    {
        return $this->getClause();

//        return $this;
    }

    // not multiple rows
    // ->insert(['id' => 1, 'name' => 'Test']);
    // insert into table (`c1`, `c2`) values ('v1', 'v2')
    // multiple rows
    // ->insert(['id' => 1, 'name' => 'Test'], ['id' => 2, 'name' => 'Test2']);
    // or
    // ->insert([['id' => 1, 'name' => 'Test'], ['id' => 2, 'name' => 'Test2']]);
    // insert into table (`c1`, `c2`) values ('v1', 'v2') ('v3', 'v4')
    /**
     * @param mixed ...$columnsWithValues
     * @throws Exception
     */
    public function insert(array ...$columnsWithValues)
    {
        $this->insertClauseBinder($columnsWithValues);

        // TODO return value
    }

    // not multiple rows
    // ->insertWithIgnore(['id' => 1, 'name' => 'Test']);
    // insert ignore into table (`c1`, `c2`) values ('v1', 'v2')
    // multiple rows
    // ->insertWithIgnore(['id' => 1, 'name' => 'Test'], ['id' => 2, 'name' => 'Test2']);
    // or
    // ->insertWithIgnore([['id' => 1, 'name' => 'Test'], ['id' => 2, 'name' => 'Test2']]);
    // insert ignore into table (`c1`, `c2`) values ('v1', 'v2') ('v3', 'v4')
    /**
     * @param mixed ...$columnsWithValues
     * @throws Exception
     */
    public function insertOrIgnore(array ...$columnsWithValues)
    {
        $this->insertClauseBinder($columnsWithValues, null, true);

        // TODO return value
    }

    // ->insertUsing(['id', 'name'], $q->where(['id' => 1])->get('id', 'name'));
    // insert into table (`id`, `name`) values (query result values)
    /**
     * @param array $columns
     * @param $query
     * @throws Exception
     */
    public function insertUsing(array $columns, $query)
    {
        $this->insertClauseBinder($columns, $query);

        // TODO return value
    }

    // ->upsert(['id' => 1, 'name'=> 'Test'], ['id', 'name']);
    // INSERT INTO `users` (`id`, `name`) VALUES (1, Test) ON DUPLICATE KEY UPDATE `id` = VALUES(`id`), `name` = VALUES(`name`)
    // If not exists second array unique keys in table -> insert first array columns with values
    // If exists second array unique keys in table -> if empty third array -> update first array columns with values
    //                                                if not empty third array ->  update third array columns with first array values
    /**
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
     * @param array $columnsWithValues
     */
    public function update(array $columnsWithValues)
    {
        $this->updateClauseBinder($columnsWithValues);

        // TODO return value
    }

    /**
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
     * @param string $column
     * @param int|float|string $amount
     */
    public function increment(string $column, int|float|string $amount = 1)
    {
        $this->unaryOperatorsClauseBinder($column, $amount);

        // TODO return update value
    }

    /**
     * @param array $columns
     */
    public function incrementEach(array $columns)
    {
        $this->unaryOperatorsClauseBinder($columns);

        // TODO return update value
    }

    /**
     * @param string $column
     * @param int|float|string $amount
     */
    public function decrement(string $column, int|float|string $amount = 1)
    {
        $this->unaryOperatorsClauseBinder($column, $amount, '-');

        // TODO return update value
    }

    /**
     * @param array $columns
     */
    public function decrementEach(array $columns)
    {
        $this->unaryOperatorsClauseBinder($columns, 1, '-');

        // TODO return update value
    }

    /**
     * @param string|null $uniqueValue
     * @param string $uniqueColumn
     */
    public function delete(string|null $uniqueValue = null, string $uniqueColumn = 'id')
    {
        $this->deleteClauseBinder($uniqueValue, $uniqueColumn);

        // TODO return delete response value
    }

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