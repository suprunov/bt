<?php

namespace App\Models;

use App\Entities\ProductDeferred;

class ProductDeferredModel extends BaseModel
{
    protected $table            = 'products_deferred';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = ProductDeferred::class;
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'product_id',
        'session_id',
        'type',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // Validation
    protected $validationRules      = [
        'product_id' => 'required',
        'session_id' => 'required',
        'type'       => 'required|in_list[favorite,compared]',
    ];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    /**
     * Basic query for record search.
     * This method works only with dbCalls.
     *
     * @param array<string, string> $filter  [session, product, type]
     *                                       A set of filters as an array <db field alias> => <db value>.
     * @param array<string, string> $sort    [id, date]
     *                                       A set of sort field aliases as an array <db field alias> => <order direction(desc|asc)>.
     * @param array<int, int>       $limit   One-dimensional array <offset> => <limit> to limit the result set.
     * @param array<string, string> $options [hints[noCache, calcRows, distinct], group[], having[]]
     *                                       A set of additional query settings as an array <setting name> => <setting value>.
     * @param array                 $fields  A simple array of database field aliases to be returned to the result set.
     *
     * @return $this
     */
    public function query(array $filter, array $sort = [], array $limit = [0 => 1], array $options = [], array $fields = ['id']): static
    {
        $builder = $this->builder();

        // query hints
        $this->queryHints( $options['hints'] ?? []);
        // select
        $this->querySelect($fields);
        // where
        foreach ($this->extendFilter($filter) as $filterKey => $filterData) {
            if ($filterKey == 'product') {
                $builder->where("{$this->table}.product_id {$filterData->operator}", $filterData->values);
            } elseif ($filterKey == 'session') {
                $builder->where("{$this->table}.session_id {$filterData->operator}", $filterData->values);
            }
        }
        // group
        $this->queryGroup($options['group'] ?? []);
        // having
        $this->queryHaving($options['having'] ?? []);
        // sort
        foreach ($sort as $order => $direction) {
            switch ($order) {
                case 'id': $builder->orderBy("{$this->table}.id", $direction); break;
                case 'date': $builder->orderBy("{$this->table}.updated_at", $direction); break;
            }
        }
        // limit
        $this->queryLimit($limit);

        return $this;
    }

    /**
     * Generic query from Products "query" and Deferred products "query".
     *
     * @param array $filter [session, product, type, location]
     * @param array $sort
     * @param array $limit
     * @param array $options
     * @param array $fields
     *
     * @return $this
     *
     * @see ProductDeferredModel::query
     * @see ProductModel::query
     */
    public function queryProducts(array $filter, array $sort = [], array $limit = [0 => 1], array $options = [], array $fields = []): static
    {
        $extendedFilter = $this->extendFilter($filter);

        // Build the search query
        $this->query(
            $this->shortenFilter($extendedFilter, ['session', 'product', 'type']),
            $sort,
            $limit,
            $options,
            ['product_id id']
        );

        $productSearchBuilder = model(ProductModel::class)->query(
            filter: $this->shortenFilter($extendedFilter, ['location']) + ['published' => true],
            limit: []
        )->builder();

        $this->builder()->join(
            "({$productSearchBuilder->getCompiledSelect()}) main",
            " {$this->table}.product_id = main.id", '',
            false
        );

        $productSearchBuilder->resetQuery();

        return $this;
    }

    /**
     * Get a list of Deferred products (with extended data).
     *
     * @param array $filter [session, product, type, location]
     * @param array $sort
     * @param array $limit
     * @param array $options
     * @param array $fields
     *
     * @return $this
     *
     * @see ProductRecentModel::query
     * @see ProductModel::query
     */
    public function getProducts(array $filter, array $sort = [], array $limit = [0 => 1], array $options = [], array $fields = []): array
    {
        // Get products IDs
        $productsFound = $this->queryProducts($filter, $sort, $limit, $options)->findItems();

        return (model(ProductModel::class)->getExtendedProducts($productsFound, $filter, $fields));
    }

    public function add(string $sessionId, int $productId, string $type): bool
    {
        $this->replace([
            'product_id' => $productId,
            'session_id' => $sessionId,
            'type'       => $type,
        ]);

        return (bool)$this->db->affectedRows();
    }

    public function remove(string $sessionId, int $productId, string $type): bool
    {
        $this->where([
                'product_id' => $productId,
                'session_id' => $sessionId,
                'type'       => $type,
            ])
            ->delete();

        return (bool)$this->db->affectedRows();
    }

    public function changeSessionId(string $sessionIdOld, string $sessionIdNew): int
    {
        $builder = $this->builder();

        $this->db->transBegin();

        $builder->ignore()
            ->set('session_id', $sessionIdNew)
            ->where('session_id', $sessionIdOld)
            ->update();
        $affectedRows = $this->db->affectedRows();

        $builder->delete(['session_id' => $sessionIdOld]);
        $affectedRows += $this->db->affectedRows();

        $this->db->transCommit();

        RETURN $affectedRows;
    }

}
