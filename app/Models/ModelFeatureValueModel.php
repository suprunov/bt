<?php

namespace App\Models;

use App\Entities\ModelFeatureValue;

class ModelFeatureValueModel extends BaseModel
{
    protected $table            = 'models_features_values';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = ModelFeatureValue::class;
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'model_id',
        'feature_id',
        'value_id',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // Validation
    protected $validationRules      = [
        'model_id' => 'required',
        'feature_id' => 'required',
        'value_id' => 'required',
    ];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    /**
     * Basic query for record search.
     * This method works only with dbCalls.
     *
     * @param array<string, string> $filter  [model]
     *                                       A set of filters as an array <db field alias> => <db value>.
     * @param array<string, string> $sort    A set of sort field aliases as an array <db field alias> => <order direction(desc|asc)>.
     * @param array<int, int>       $limit   One-dimensional array <offset> => <limit> to limit the result set.
     * @param array<string, string> $options [hints[noCache, calcRows, distinct], group[], having[]]
     *                                       A set of additional query settings as an array <setting name> => <setting value>.
     * @param array                 $fields  [feature, value]
     *                                       A simple array of database field aliases to be returned to the result set.
     *
     * @return $this
     */
    public function query(array $filter, array $sort = [], array $limit = [0 => 1], array $options = [], array $fields = []): static
    {
        $builder = $this->builder();

        // query hints
        $this->queryHints($options['hints'] ?? []);

        // select
        $builder->select("{$this->table}.id, {$this->table}.model_id, {$this->table}.feature_id, {$this->table}.value_id");
        if (in_array('feature', $fields)) {
            $builder->select("JSON_OBJECT(
                'id', f.id,
                'name', f.name,
                'code', f.code,
                'reference', f.reference,                
                'type', f.type,
                'published', f.published) feature")
                ->join(tbl('features') . ' f', "f.id = {$this->table}.feature_id");
        }
        if (in_array('value', $fields)) {
            $builder->select("JSON_OBJECT(
                'id', fv.id,
                'value', fv.value,
                'value_alias', fv.value_alias,
                'value_double', fv.value_double,                
                'code', fv.code) value")
                ->join(tbl('features_values') . ' fv', "fv.id = {$this->table}.value_id");
        }

        // where
        if ($filter['model'] ?? false) {
            $builder->where('model_id', $filter['model']);
        }

        // sort
        $this->querySort($sort);
        // limit
        $this->queryLimit($limit);

        return $this;
    }

}
