<?php

namespace App\Models;

use App\Entities\VehicleBrand;

class VehicleBrandModel extends BaseModel
{
    protected $table            = 'vehicles_brands';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = false;
    protected $returnType       = VehicleBrand::class;
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'id',
        'name',
        'name_rus',
        'type',
        'published',
        'site_map',
        'picture_id',
        'aliases',
        'description',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // Validation
    protected $validationRules      = [
        'name' => 'required',
        'type' => 'required|in_list[car,moto]',
    ];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    /**
     * Basic query to find the Brand.
     * Works with the current query builder instance.
     *
     * @param array<string, string> $filter  [id, searchQuery, published]
     *                                       A set of filters as an array <db field alias> => <db value>.
     * @param array<string, string> $sort    [name]
     *                                       A set of sort field aliases as an array <db field alias> => <order direction(desc|asc)>.
     * @param array<int, int>       $limit   One-dimensional array <offset> => <limit> to limit the result set.
     * @param array<string, string> $options [hints[noCache, calcRows, distinct]]
     *                                       A set of additional query settings as an array <setting name> => <setting value>.
     * @param array                 $fields  [pictures, description] - extra fields to be returned to the result set.
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
                JSON_OBJECT('value', {$this->table}.name, 'value_rus', {$this->table}.name_rus) name,
                {$this->table}.published,
                fv.code
            ")
            ->join(tbl('features_values') . ' fv', "fv.id = {$this->table}.id");
        // select - description
        if (in_array('description', $fields)) {
            $builder->select("{$this->table}.aliases, {$this->table}.description");
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
        if ($filter['searchQuery'] ?? false) {
            $builder->like("{$this->table}.name", $filter['searchQuery']);
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
