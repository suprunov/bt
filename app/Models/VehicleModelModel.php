<?php

namespace App\Models;

use App\Entities\VehicleModel;

class VehicleModelModel extends BaseModel
{
    protected $table            = 'vehicles_models';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = VehicleModel::class;
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'brand_id',
        'name',
        'name_rus',
        'published',
        'code',
        'sitemap',
        'aliases',
        'guid',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // Validation
    protected $validationRules = [
        'brand_id' => 'required',
        'name'     => 'required',
        'code'     => 'required',
        'guid'     => 'required',
    ];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;
}
