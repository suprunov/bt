<?php

namespace App\Models;

use App\Entities\Model;

class ModelModel extends BaseModel
{
    protected $table            = 'models';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = false;
    protected $returnType       = Model::class;
    protected $useSoftDeletes   = false;
    protected $protectFields    = false;
    protected $allowedFields    = [
        'id',
        'brand_id',
        'name',
        'product_type_id',
        'novelty',
        'obsolete_model_id',
        'description',
        'aliases',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // Validation
    protected $validationRules      = [
        'brand_id' => 'required',
        'name'     => 'required',
    ];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    /**
     * Basic query to find the Model.
     * Works with the current query builder instance.
     *
     * @param array<string, string> $filter  [id, productType, emptyDescription, searchQuery]
     *                                       A set of filters as an array <db field alias> => <db value>.
     * @param array<string, string> $sort    [name]
     *                                       A set of sort field aliases as an array <db field alias> => <order direction(desc|asc)>.
     * @param array<int, int>       $limit   One-dimensional array <offset> => <limit> to limit the result set.
     * @param array<string, string> $options [hints[noCache, calcRows, distinct]]
     *                                       A set of additional query settings as an array <setting name> => <setting value>.
     * @param array                 $fields  [description, brand, pictures, reviewTotals] - extra fields to be returned to the result set.
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
                {$this->table}.name,
                fv.code,
                JSON_OBJECT('id', pt.id, 'code', pt.code, 'name', pt.name, 'name_plural', pt.name_plural) type
            ")
            ->join(tbl('features_values') . ' fv', "fv.id = {$this->table}.id")
            ->join(tbl('products_types') . ' pt', "pt.id = {$this->table}.product_type_id");

        if (in_array('description', $fields)) {
            $builder->select("{$this->table}.description, {$this->table}.aliases");
        }
        // select - brand
        if (in_array('brand', $fields)) {
            $builder->select(
                "JSON_OBJECT(
                    'id', fv_brand.id, 
                    'code', fv_brand.code, 
                    'name', JSON_OBJECT('value', b.name, 'value_eng', b.name_eng, 'value_rus', b.name_rus) 
                    ) brand"
                )
                ->join(tbl('features_values') . ' fv_brand', "fv_brand.id = {$this->table}.brand_id")
                ->join(tbl('brands') . ' b', "b.id = {$this->table}.brand_id");
        }
        // select - pictures
        if (in_array('pictures', $fields))
        {
            $subQueryPicturesBuilder = $this->db->table(tbl('models_pictures') . ' mpic');
            $subQueryPicturesBuilder->select("
                JSON_ARRAYAGG(JSON_OBJECT(
                'id', picv.picture_id,
                'variation_id', picv.id,
                'type_code', pict.code,
                'variation_code', pictv.code,                
                'sort', mpic.sort,
                'color', JSON_OBJECT('id', mpic.color_id, 'name', fv_color.value),
                'path', picv.path,
                'filename', picv.filename,
                'extension', picv.extension,
                'width', picv.width,
                'height', picv.height)) 
            ", false);
            $subQueryPicturesBuilder->join(tbl('pictures') . ' pic', 'pic.id = mpic.picture_id')
                ->join(tbl('pictures_variations') . ' picv', 'picv.picture_id = pic.id')
                ->join(tbl('pictures_types_variations') . ' pictv', "pictv.id = picv.type_variation_id")
                ->join(tbl('pictures_types') . ' pict', 'pict.id = pictv.type_id')
                ->join(tbl('features_values') . ' fv_color', 'fv_color.id = mpic.color_id', 'left')
                ->where("mpic.model_id = {$this->table}.id")
                ->orderBy('mpic.sort', 'asc');
            $builder->selectSubquery($subQueryPicturesBuilder, 'pictures');

            // colors
            $subQueryColorsBuilder = $this->db->table(tbl('products_features_values') . ' pfv');
            $subQueryColorsBuilder->select("
                JSON_ARRAYAGG(DISTINCT JSON_OBJECT(
                'id', fv_col.id,
                'name', fv_col.value)) colors
            ", false);
            $subQueryColorsBuilder->join(tbl('products') . ' p', 'p.id = pfv.product_id')
                ->join(tbl('products_features_values') . ' pfv_col', 'pfv_col.type_id = p.type_id and pfv_col.product_id = p.id')
                ->join(tbl('features') . ' f', 'f.id = pfv_col.feature_id')
                ->join(tbl('features_values') . ' fv_col', 'fv_col.id = pfv_col.value_id')
                ->where("pfv.value_id = {$this->table}.id")
                ->where('f.reference', 'color')
                ->where('p.published', 1);
            $builder->selectSubquery($subQueryColorsBuilder, 'colors');
        }

        // select - review totals (qty, average grade)
        if (in_array('reviewTotals', $fields))
        {
            $subQueryReviewsBuilder = $this->db->table(tbl('models_reviews') . ' m_rev')
                ->select("JSON_OBJECT('qty', COUNT(m_rev.id), 'average', ROUND(AVG(m_rev.grade), 1))")
                ->where("m_rev.model_id = {$this->table}.id")
                ->groupBy("m_rev.model_id");

            // $builder->selectSubquery($subQueryReviewsBuilder, 'review_totals');
            $builder->select('COALESCE((' . $subQueryReviewsBuilder->getCompiledSelect() . "), JSON_OBJECT('qty', 0, 'average', 0)) review_totals", false);
        }

        // filter
        if ($filter['id'] ?? false) {
            $builder->where("{$this->table}.id", $filter['id']);
        }
        if (($filter['emptyDescription'] ?? false) && in_array('description', $fields)) {
            $builder->where("{$this->table}.description IS NULL");
        }
        if ($filter['searchQuery'] ?? false) {
            $builder->like("{$this->table}.name", $filter['searchQuery']);
        }
        if ($filter['productType'] ?? false) {
            if (is_array($filter['productType']))
                $builder->whereIn("{$this->table}.product_type_id", $filter['productType']);
            else
                $builder->where("{$this->table}.product_type_id", $filter['productType']);
        }

        // sort
        foreach ($sort as $order => $direction) {
            switch ($order) {
                case 'name': $builder->orderBy("{$this->table}.name", $direction); break;
            }
        }

        // limit
        $this->queryLimit($limit);

        return $this;
    }

}
