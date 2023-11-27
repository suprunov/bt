<?php

namespace App\Models;

use App\Entities\ProductType;

class ProductTypeModel extends BaseModel
{
    protected $table            = 'products_types';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = ProductType::class;
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'id',
        'parent_id',
        'name',
        'name_plural',
        'code',
        'published',
        'qty_set',
        'obsolete_type_id',
        'sort',
        'guid',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // Validation
    protected $validationRules      = [
        'name' => 'required',
        'code' => 'required',
        'guid' => 'required',
    ];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    /**
     * Basic query to find the Product type.
     * Works with the current query builder instance.
     *
     * @param array<string, string> $filter  [id, published]
     *                                       A set of filters as an array <db field alias> => <db value>.
     * @param array<string, string> $sort    [name, sort]
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

        // select
        $builder->select("
                {$this->table}.id,
                {$this->table}.parent_id,
                {$this->table}.code,
                JSON_OBJECT('value', {$this->table}.name, 'value_plural', {$this->table}.name_plural) name,
                {$this->table}.published,
                {$this->table}.qty_set,
                {$this->table}.sort
            ");

        // filter
        if ($filter['id'] ?? false) {
            $builder->where("{$this->table}.id", $filter['id']);
        }
        if ($filter['published'] ?? false) {
            $builder->where("{$this->table}.published", 1);
        }

        // sort
        foreach ($sort as $order => $direction) {
            switch ($order) {
                case 'sort': $builder->orderBy("{$this->table}.sort", $direction); break;
                case 'name': $builder->orderBy("{$this->table}.name", $direction); break;
            }
        }

        // limit
        $this->queryLimit($limit);

        return $this;
    }

    public function buildTree(array $categories, $parentId = null): array
    {
        $tree = [];
        foreach ($categories as $category) {
            if ($category->parent_id === $parentId) {
                $category->names = $category->name; // For admin area API - for tree filter module
                $category->name  = $category->name->value_plural ?: $category->name->value; // For admin area API - for tree filter module
                $category->children = $this->buildTree($categories, $category->id);
                $tree[] = $category;
            }
        }
        return $tree;
    }
}
