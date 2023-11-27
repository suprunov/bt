<?php

namespace App\Models;

use App\Entities\ModelPicture;

class ModelPictureModel extends BaseModel
{
    protected $table            = 'models_pictures';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = ModelPicture::class;
    protected $useSoftDeletes   = false;
    protected $protectFields    = false;
    protected $allowedFields    = [
        'model_id',
        'color_id',
        'picture_id',
        'sort',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // Validation
    protected $validationRules = [
        'model_id'   => 'required',
        'picture_id' => 'required',
    ];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    /**
     * Basic query to find the Model picture.
     * Works with the current query builder instance.
     *
     * @param array<string, string> $filter  [id, model, color, picture]
     *                                       A set of filters as an array <db field alias> => <db value>.
     * @param array<string, string> $sort    [sort]
     *                                       A set of sort field aliases as an array <db field alias> => <order direction(desc|asc)>.
     * @param array<int, int>       $limit   One-dimensional array <offset> => <limit> to limit the result set.
     * @param array<string, string> $options [hints[noCache, calcRows, distinct]]
     *                                       A set of additional query settings as an array <setting name> => <setting value>.
     * @param array                 $fields  [] - extra fields to be returned to the result set.
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
                {$this->table}.color_id,
                {$this->table}.picture_id,
                {$this->table}.sort
            ");

        // filter
        if ($filter['id'] ?? false) {
            $builder->where("{$this->table}.id", $filter['id']);
        }
        if ($filter['model'] ?? false) {
            $builder->where("{$this->table}.model_id", $filter['model']);
        }
        if ($filter['color'] ?? false) {
            $builder->where("{$this->table}.color_id", $filter['color']);
        }
        if ($filter['picture'] ?? false) {
            $builder->where("{$this->table}.picture_id", $filter['picture']);
        }

        // sort
        foreach ($sort as $order => $direction) {
            switch ($order) {
                case 'sort': $builder->orderBy("{$this->table}.sort", $direction); break;
            }
        }

        // limit
        $this->queryLimit($limit);

        return $this;
    }

}
