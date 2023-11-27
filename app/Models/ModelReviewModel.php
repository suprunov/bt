<?php

namespace App\Models;

use App\Entities\ModelReview;

class ModelReviewModel extends BaseModel
{
    protected $table            = 'models_reviews';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = false;
    protected $returnType       = ModelReview::class;
    protected $useSoftDeletes   = false;
    protected $protectFields    = false;
    protected $allowedFields    = [];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    //protected $createdField  = 'created_at';
    //protected $updatedField  = 'updated_at';

    // Validation
    protected $validationRules      = [];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    /**
     * Basic query to find the Model reviews.
     * Works with the current query builder instance.
     *
     * @param array<string, string> $filter  [id, model, published]
     *                                       A set of filters as an array <db field alias> => <db value>.
     * @param array<string, string> $sort    [id]
     *                                       A set of sort field aliases as an array <db field alias> => <order direction(desc|asc)>.
     * @param array<int, int>       $limit   One-dimensional array <offset> => <limit> to limit the result set.
     * @param array<string, string> $options [hints[noCache, calcRows, distinct]]
     *                                       A set of additional query settings as an array <setting name> => <setting value>.
     * @param array                 $fields  [comments] - extra fields to be returned to the result set.
     *
     * @return $this
     */
    public function query(array $filter, array $sort = [], array $limit = [0 => 1], array $options = [], array $fields = []): static
    {
        $builder = $this->builder();

        // query hints
        $this->queryHints($options['hints'] ?? []);

        // select - main
        $builder->select("
                {$this->table}.id,
                {$this->table}.model_id,
                {$this->table}.grade,
                {$this->table}.recommend recommended,
                {$this->table}.date,
                {$this->table}.active published,
                {$this->table}.author,
                {$this->table}.manager,
                {$this->table}.usage_time,
                {$this->table}.source
            ");
        // select - comments
        if (in_array('comments', $fields)) {
            $builder->select("{$this->table}.text comment, {$this->table}.pro advantages, {$this->table}.contra disadvantages");
        }

        // filter
        if ($filter['id'] ?? false) {
            $builder->where("{$this->table}.id", $filter['id']);
        }
        if ($filter['model'] ?? false) {
            $builder->where("{$this->table}.model_id", $filter['model']);
        }
        if ($filter['published'] ?? false) {
            $builder->where("{$this->table}.active", 1);
        }

        // sort
        foreach ($sort as $order => $direction) {
            switch ($order) {
                case 'id': $builder->orderBy("{$this->table}.id", $direction); break;
            }
        }

        // limit
        $this->queryLimit($limit);

        return $this;
    }

}
