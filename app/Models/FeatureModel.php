<?php

namespace App\Models;

use App\Entities\Feature;

class FeatureModel extends BaseModel
{
    protected $table            = 'features';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = Feature::class;
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'name',
        'code',
        'reference',
        'type',
        'published',
        'description',
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
        'reference' => 'required|in_list[none,miscellanea,brand,model,color,car_brand,color_group,country]',
        'type' => 'required|in_list[varchar,double,boolean]',
        'guid' => 'required',
    ];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = [];
    protected $afterInsert    = [];
    protected $beforeUpdate   = [];
    protected $afterUpdate    = [];
    protected $beforeFind     = [];
    protected $afterFind      = [];
    protected $beforeDelete   = [];
    protected $afterDelete    = [];

    /**
     * Find Entity by unique Reference
     *
     * @param ?string $reference
     *
     * @return array|object|null
     */
    public function findByReference(?string $reference)
    {
        if ($reference === null)
            return null;

        return $this->where('reference', $reference)
            ->first();
    }

}
