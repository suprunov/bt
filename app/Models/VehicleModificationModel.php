<?php

namespace App\Models;

use App\Entities\VehicleModification;

class VehicleModificationModel extends BaseModel
{
    protected $table            = 'vehicles_modifications';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = VehicleModification::class;
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'model_id',
        'name',
        'code',
        'body',
        'year_from',
        'year_to',
        'published',
        'guid',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // Validation
    protected $validationRules = [
        'model_id'     => 'required',
        'name'         => 'required',
        'code'         => 'required',
        'body'         => 'required',
        'year_from'    => 'required|integer|exact_length[4]',
        'year_to'      => 'required|integer|exact_length[4]',
        'guid'         => 'required',
    ];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;
}
