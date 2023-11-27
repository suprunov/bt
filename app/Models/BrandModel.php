<?php

namespace App\Models;

use App\Entities\Brand;

class BrandModel extends BaseModel
{
    protected $table            = 'brands';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = false;
    protected $returnType       = Brand::class;
    protected $useSoftDeletes   = false;
    protected $protectFields    = false;
    protected $allowedFields    = [
        'name',
        'name_eng',
        'name_rus',
        'published',
        'picture_id',
        'country_id',
        'priority_qty',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // Validation
    protected $validationRules      = [
        'name'     => 'required',
        'name_eng' => 'required',
    ];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    /**
     * Basic query to find the Brand.
     * Works with the current query builder instance.
     *
     * @param array<string, string> $filter  [id, published, productType, emptyDescription, searchQuery]
     *                                       A set of filters as an array <db field alias> => <db value>.
     * @param array<string, string> $sort    [name]
     *                                       A set of sort field aliases as an array <db field alias> => <order direction(desc|asc)>.
     * @param array<int, int>       $limit   One-dimensional array <offset> => <limit> to limit the result set.
     * @param array<string, string> $options [hints[noCache, calcRows, distinct]]
     *                                       A set of additional query settings as an array <setting name> => <setting value>.
     * @param array                 $fields  [description, productType, pictures] - extra fields to be returned to the result set.
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
                JSON_OBJECT('value', {$this->table}.name, 'value_eng', {$this->table}.name_eng, 'value_rus', {$this->table}.name_rus) name,
                {$this->table}.published,
                {$this->table}.priority_qty,
                fv.code,
                JSON_OBJECT('id', c.id, 'name', c.name, 'name_plural', c.name_plural) country
            ")
            ->join(tbl('features_values') . ' fv', "fv.id = {$this->table}.id")
            ->join(tbl('countries') . ' c', "c.id = {$this->table}.country_id", 'left');
        // select - product type
        if (in_array('productType', $fields)) {
            $featureBrandBuilder = $this->db->table(tbl('features') . ' f')
                ->select('f.id')
                ->where("f.reference = 'brand'")
                ->limit(1);
            $typeBuilder = $this->db->table(tbl('products_features_values') . ' pfv')
                ->select("JSON_OBJECT('id', pt.id, 'code', pt.code, 'name', pt.name, 'name_plural', pt.name_plural)")
                ->join(tbl('products') . ' p', 'p.id = pfv.product_id')
                ->join(tbl('products_types') . ' pt', 'pt.id = pfv.type_id')
                ->where('pfv.feature_id = (' . $featureBrandBuilder->getCompiledSelect() . ')', null, false)
                ->where("pfv.value_id = {$this->table}.id")
                ->where('p.published', 1)
                ->limit(1);
            $builder->selectSubquery($typeBuilder, 'type');
        }
        // select - description
        if (in_array('description', $fields)) {
            $builder->select('be.description, be.aliases')
                ->join(tbl('brands_extras') . ' be', "{$this->table}.id = be.id");
        }
        // select - pictures
        if (in_array('pictures', $fields))
        {
            // TODO move to productPictureModel
            $subQueryPicturesBuilder = $this->db->table(tbl('pictures') . ' pic');
            $subQueryPicturesBuilder->select("
                JSON_ARRAYAGG(JSON_OBJECT(
                'id', picv.picture_id,
                'variation_id', picv.id,
                'type_code', pict.code,
                'variation_code', pictv.code,                
                'sort', null,
                'path', picv.path,
                'filename', picv.filename,
                'extension', picv.extension,
                'width', picv.width,
                'height', picv.height)) 
            ", false);

            $subQueryPicturesBuilder->join(tbl('pictures_variations') . ' picv', 'picv.picture_id = pic.id')
                ->join(tbl('pictures_types_variations') . ' pictv', "pictv.id = picv.type_variation_id")
                ->join(tbl('pictures_types') . ' pict', 'pict.id = pictv.type_id')
                ->where("pic.id = {$this->table}.picture_id");

            $builder->selectSubquery($subQueryPicturesBuilder, 'picture');
        }

        // filter
        if ($filter['id'] ?? false) {
            $builder->where("{$this->table}.id", $filter['id']);
        }
        if ($filter['published'] ?? false) {
            $builder->where("{$this->table}.published", 1);
        }
        if (($filter['emptyDescription'] ?? false) && in_array('description', $fields)) {
            $builder->where("be.description IS NULL");
        }
        if ($filter['searchQuery'] ?? false) {
            $builder->like("{$this->table}.name", $filter['searchQuery']);
        }
        if ($filter['productType'] ?? false) {
            $featureBrandBuilder = $this->db->table(tbl('features') . ' f')
                ->select('f.id')
                ->where("f.reference = 'brand'")
                ->limit(1);
            $existsBuilder = $this->db->table(tbl('products_features_values') . ' pfv')
                ->select('1')
                ->join(tbl('products') . ' p', 'p.id = pfv.product_id')
                ->where('pfv.type_id', $filter['productType'])
                ->where('pfv.feature_id = (' . $featureBrandBuilder->getCompiledSelect() . ')', null, false)
                ->where("pfv.value_id = {$this->table}.id")
                ->where('p.published', 1)
                ->limit(1);
            $builder->where('EXISTS (' . $existsBuilder->getCompiledSelect() . ')', null, false);
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
