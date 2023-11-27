<?php

namespace App\Models;

trait BaseModelTrait
{
    /**
     * Basic query for record search.
     * This method works only with dbCalls.
     *
     * @param array<string, string> $filter  A set of filters as an array <db field alias> => <db value>.
     * @param array<string, string> $sort    A set of sort field aliases as an array <db field alias> => <order direction(desc|asc)>.
     * @param array<int, int>       $limit   One-dimensional array <offset> => <limit> to limit the result set.
     * @param array<string, string> $options [hints[noCache, calcRows, distinct], group[], having[]]
     *                                       A set of additional query settings as an array <setting name> => <setting value>.
     * @param array                 $fields  A simple array of database field aliases to be returned to the result set.
     *
     * @return $this
     */
    public function query(array $filter, array $sort = [], array $limit = [0 => 1], array $options = [], array $fields = []): static
    {
        // query hints
        $this->queryHints( $options['hints'] ?? []);
        // select
        $this->querySelect($fields);
        // where
        $this->queryFilter($filter);
        // group
        $this->queryGroup($options['group'] ?? []);
        // having
        $this->queryHaving($options['having'] ?? []);
        // sort
        $this->querySort($sort);
        // limit
        $this->queryLimit($limit);

        return $this;
    }

    public function queryHints(array $hints): void
    {
        $builder = $this->builder();

        $hintsArr = [];
        if ($hints['noCache'] ?? false) {
            $hintsArr[] = 'SQL_NO_CACHE';
        }
        if ($hints['calcRows'] ?? false) {
            $hintsArr[] = 'SQL_CALC_FOUND_ROWS';
        }
        if ($hints['distinct'] ?? false) {
            $builder->distinct();
        }
        if (count($hintsArr)) {
            $builder->select(implode(' ', $hintsArr) . " ''", false);
        }
    }

    public function querySelect(array $fields): void
    {
        if (count($fields)) {
            foreach ($fields as $field) {
                $this->builder()->select($this->table . '.' . $field);
            }
        } else {
            $this->builder()->select($this->table . '.*');
        }
    }

    // TODO notIn != > < or
    public function queryFilter(array $filter): void
    {
        foreach ($filter as $filterCode => $filterValue) {
            if (is_array($filterValue))
                $this->builder()->whereIn($filterCode, $filterValue);
            else {
                $this->builder()->where($filterCode, $filterValue);
            }
        }
    }

    public function extendFilter(array $filter): array
    {
        $extendedFilter = [];
        foreach ($filter as $filterKey => $filterValue) {
            list($filterCode, $filterOperator) = array_pad(explode(' ', $filterKey, 2), 2, '');
            $extendedFilter[$filterCode] = (object)[
                'key'      => $filterKey,
                'operator' => $filterOperator,
                'values'   => $filterValue,
            ];
        }
        return $extendedFilter;
    }

    public function shortenFilter(array $filter, array $keys = []): array
    {
        $shortenedFilter = [];
        foreach ($filter as $filterCode => $filterData) {
            if (! empty($keys) && in_array($filterCode, $keys))
                $shortenedFilter[$filterData->key] = $filterData->values;
        }
        return $shortenedFilter;
    }

    public function queryGroup(array $group): void
    {
        foreach ($group as $groupField) {
            $this->builder()->groupBy($groupField);
        }
    }

    public function queryHaving(array $having): void
    {
        foreach ($having as $havingField => $havingValue) {
            $this->builder()->having($havingField, $havingValue);
        }
    }

    public function querySort(array $sort): void
    {
        foreach ($sort as $sortField => $sortDirection) {
            $this->builder()->orderBy($sortField, $sortDirection);
        }
    }

    public function queryLimit(array $limit): void
    {
        foreach ($limit as $limitOffset => $limitValue) {
            $this->builder()->limit($limitValue, $limitOffset);
        }
    }

    /**
     * Works with the current Query Builder instance to return results.
     * This method works only with dbCalls.
     *
     * // TODO do we need this? why not use findAll() ?
     *
     * @return array
     */
    public function findItems(): array
    {
        return $this->builder()
            ->get()
            ->getResult($this->tempReturnType);
    }

    /**
     * Find Entity by GUID
     *
     * @param ?string $guid
     *
     * @return array|object|null
     */
    public function findByGuid(?string $guid): object|array|null
    {
        if ($guid === null)
            return null;

        return $this->where('guid', $guid)
            ->first();
    }

    /**
     * Find Entity by unique Name
     *
     * @param ?string $name
     *
     * @return array|object|null
     */
    public function findByName(?string $name): object|array|null
    {
        if ($name === null)
            return null;

        return $this->where('name', $name)
            ->first();
    }

    /**
     * Find Entity by unique Code
     *
     * @param ?string $code
     *
     * @return array|object|null
     */
    public function findByCode(?string $code): object|array|null
    {
        if ($code === null)
            return null;

        return $this->where('code', $code)
            ->first();
    }

    /**
     * @throws \Exception
     */
    public function foundRows(): int
    {
        return $this->db->query("SELECT FOUND_ROWS() total")->getFirstRow()->total;
    }
}