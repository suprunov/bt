<?php

namespace App\Models;

use App\Entities\FeatureFilter;

class FeatureFilterModel extends BaseModel
{
    protected $table            = 'features_filters';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = FeatureFilter::class;
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'product_type_id',
        'feature_id',
        'name',
        'url_template',
        'url_sort',
        'published',
        'card_published',
        'card_sort',
        'card_group_id',
        'card_similar',
        'preview_published',
        'preview_sort',
        'filter_published',
        'filter_sort',
        'filter_type',
        'filter_no_index',
        'filter_value_separator',
        'description',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // Validation
    protected $validationRules      = [
        'product_type_id' => 'required',
        'feature_id'      => 'required',
        'name'            => 'required',
    ];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    /**
     * Basic query for Product Filter search.
     * Works with the current query builder instance.
     *
     * @param array<string, string> $filter  [published, product_type]
     *                                       A set of filters as an array <db field alias> => <db value>.
     * @param array<string, string> $sort    [filter, card, preview]
     *                                       A set of sort field aliases as an array <db field alias> => <order direction(desc|asc)>.
     * @param array<int, int>       $limit   One-dimensional array <offset> => <limit> to limit the result set.
     * @param array<string, string> $options [hints[noCache, calcRows, distinct], totals]
     *                                       A set of additional query settings as an array <setting name> => <setting value>.
     * @param array                 $fields  [feature]
     *                                       A simple array of database field aliases to be returned to the result set.
     *
     * @return $this
     */
    public function query(array $filter, array $sort = [], array $limit = [0 => 1], array $options = [], array $fields = ['feature']): static
    {
        $builder = $this->builder();

        // query hints
        $this->queryHints($options['hints'] ?? []);

        // select
        $builder->select("{$this->table}.*");

        // feature
        if (in_array('feature', $fields)) {
            $builder->join(tbl('features') . ' f', "{$this->table}.feature_id = f.id")
                ->select("JSON_OBJECT('id', f.id, 'code', f.code, 'name', f.name, 'reference', f.reference, 'type', f.type) feature");
        }

        // filter
        if (isset($filter['published'])) {
            $builder->where("{$this->table}.published", (int)$filter['published']);
        }
        if (isset($filter['product_type'])) {
            $builder->where("{$this->table}.product_type_id", $filter['product_type']);
        }

        // sort
        foreach ($sort as $order => $direction) {
            switch ($order) {
                case 'filter':  $builder->orderBy("{$this->table}.filter_sort", $direction); break;
                case 'card':    $builder->orderBy("{$this->table}.card_sort", $direction); break;
                case 'preview': $builder->orderBy("{$this->table}.preview_sort", $direction); break;
            }
        }

        // limit
        $this->queryLimit($limit);

        return $this;
    }

}
