<?php

namespace App\Models;

use App\Entities\ProductRecent;

class ProductRecentModel extends BaseModel
{
    public const OUTPUT_QTY_LIMIT = 20; // TODO to the lib

    protected $table            = 'products_recent';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = ProductRecent::class;
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'product_id',
        'session_id',
        'created_date',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'created_at'; // because of sql statement 'replace'

    // Validation
    protected $validationRules = [
        'product_id'   => 'required',
        'session_id'   => 'required',
        'created_date' => 'required',
    ];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    /**
     * Basic query for record search.
     * This method works only with dbCalls.
     *
     * @param array<string, string> $filter  [session, product]
     *                                       A set of filters as an array <db field alias> => <db value>.
     * @param array<string, string> $sort    [id, date]
     *                                       A set of sort field aliases as an array <db field alias> => <order direction(desc|asc)>.
     * @param array<int, int>       $limit   One-dimensional array <offset> => <limit> to limit the result set.
     * @param array<string, string> $options [uniqueProducts, hints[noCache, calcRows, distinct], group[], having[]]
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
                if (in_array('uniqueProducts', $options)) {
                    $uniqueProductsBuilder = $this->db->table($this->table . ' rp')
                        ->select('1', false)
                        ->where("rp.session_id = {$this->table}.session_id")
                        ->where("rp.product_id = {$this->table}.product_id")
                        ->where("rp.id > {$this->table}.id")
                        ->limit(1);
                    $builder->where("NOT EXISTS ({$uniqueProductsBuilder->getCompiledSelect()})", NULL, FALSE);
                }
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
                case 'date': $builder->orderBy("{$this->table}.created_date", $direction); break;
            }
        }
        // limit
        $this->queryLimit($limit);

        return $this;
    }

    /**
     * Generic query from Products "query" and Recent products "query".
     *
     * @param array $filter [session, product, location]
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
    public function queryProducts(array $filter, array $sort = [], array $limit = [0 => 1], array $options = [], array $fields = []): static
    {
        $extendedFilter = $this->extendFilter($filter);

        // Build the search query
        $this->query(
            $this->shortenFilter($extendedFilter, ['session', 'product']),
            $sort,
            $limit,
            $options + ['uniqueProducts'],
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
     * Get a list of Recent products (with extended data).
     *
     * @param array $filter [session, product, location]
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

    public function add(string $sessionId, int $productId): bool
    {
        $this->replace([
            'session_id' => $sessionId,
            'product_id' => $productId,
        ]);

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
