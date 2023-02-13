<?php

namespace Moarai\QueryBuilder;

use Moarai\Drivers\AvailableDbmsDrivers;
use ReflectionClass;

class QueryBuilderRepresentativeSpokesman extends QueryBuilder
{
    public function select(array|string ...$columns): self
    {
        $this->selectClauseBinder(false, $columns);

        return $this;
    }

    public function distinct(array|string ...$columns): self
    {
        $this->selectClauseBinder(true, $columns);

        return $this;
    }

    public function from(string $table): self
    {
        $this->fromClauseBinder($table);

        return $this;
    }

    public function table(string $table): self
    {
        $this->from($table);

        return $this;
    }

    public function where(string|array|callable $column, string|null $operator = null, string|null $value = ''): self
    {
        $this->baseConditionClauseBinder('', 'where', $column, $operator, $value);

        return $this;
    }

    public function whereBetween(string|callable $column, array|string|int|float $range = '', string|int|float $endOfRange = ''): self
    {
        $this->whereBetweenClauseBinder('', $column, $range, $endOfRange);

        return $this;
    }

    public function whereBetweenColumns(string|callable $column, array|string|int|float $range = '', string|int|float $endOfRange = ''): self
    {
        $this->whereBetweenClauseBinder('', $column, $range, $endOfRange, false, true);

        return $this;
    }

    public function whereIn(string|callable $column, array $setOfSupposedVariables = []): self
    {
        $this->whereInClauseBinder('', $column, $setOfSupposedVariables);

        return $this;
    }

    public function whereNull(string|callable $column): self
    {
        $this->whereNullClauseBinder('', $column, false);

        return $this;
    }

    public function whereExists(callable $callback): self
    {
        $this->whereExistsClauseBinder('', $callback);

        return $this;
    }

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

    public function whereFullText(string|array $column, string $value,
                                  string $searchModifier = FullTextSearchModifiers::NATURAL_LANGUAGE_MODE,
                                  string|null $rankingColumn = null,
                                  string|int|array $normalizationBitmask = 32): self
    {
        if ($this->getDriver() !== AvailableDbmsDrivers::MYSQL) {
            $reflectionClass = new ReflectionClass(FullTextSearchModifiers::class);

            if ($this->checkMatching($searchModifier, $reflectionClass->getConstants())) {
                $searchModifier = '';
            }
        }

        dd($normalizationBitmask);

        $this->whereFullTextClauseBinder(
            '',
            $column,
            $value,
            $searchModifier,
            $rankingColumn,
            $normalizationBitmask
        );

        return $this;
    }

    public function orderBy(string|array $column, string $direction = 'asc'): self
    {
        $this->orderByClauseBinder($column, $direction);

        return $this;
    }

    public function latest(string|array $column): self
    {
        $this->orderBy($column, 'desc');

        return $this;
    }

    public function oldest(string|array $column): self
    {
        $this->orderBy($column);

        return $this;
    }

    public function inRandomOrder(string $column): self
    {
        $this->orderByClauseBinder($column, '', true);

        return $this;
    }

    public function groupBy(string|array ...$columns): self
    {
        $this->groupByClauseBinder($columns);

        return $this;
    }

    public function having(string|array $column, string|null $operator = null, string|null $value = ''): self
    {
        $this->baseConditionClauseBinder('', 'having', $column, $operator, $value);

        return $this;
    }

    // TODO having between

    public function limit(int $count): self
    {
        $this->limitClauseBinder($count);

        return $this;
    }

    public function offset(int $count): self
    {
        $this->offsetClauseBinder($count);

        return $this;
    }

    public function skip(int $count): self
    {
        $this->offset($count);

        return $this;
    }

    public function take(int $count): self
    {
        $this->limit($count);

        return $this;
    }

    public function when(bool $value, callable $callback, callable|null $else = null): self
    {
        $this->whenClauseBinder($value, $callback, $else);

        return $this;
    }

    public function get()
    {
        $this->getClause();

        return $this;
    }

    // not multiple rows
    // ->insert(['id' => 1, 'name' => 'Test']);
    // insert into table (`c1`, `c2`) values ('v1', 'v2')
    // multiple rows
    // ->insert(['id' => 1, 'name' => 'Test'], ['id' => 2, 'name' => 'Test2']);
    // or
    // ->insert([['id' => 1, 'name' => 'Test'], ['id' => 2, 'name' => 'Test2']]);
    // insert into table (`c1`, `c2`) values ('v1', 'v2') ('v3', 'v4')
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
    public function insertWithIgnore(array ...$columnsWithValues)
    {
        $this->insertClauseBinder($columnsWithValues, null, true);

        // TODO return value
    }

    // ->insertUsing(['id', 'name'], $q->where(['id' => 1])->get('id', 'name'));
    // insert into table (`id`, `name`) values (query result values)
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
    public function upsert(array $values, string|array|null $update = null, string|array|null $uniqueBy = null)
    {
        $this->insertClauseBinder($values, null, false, true, $uniqueBy, $update);

        // TODO return value
    }
}