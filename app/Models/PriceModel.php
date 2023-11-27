<?php

namespace App\Models;

use App\Entities\Price;

class PriceModel extends BaseModel
{
    protected $table            = 'prices';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = Price::class;
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'location_id',
        'name',
        'code',
        'default',
        'available',
        'type_id',
        'guid',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // Validation
    protected $validationRules      = [
        'location_id' => 'required',
        'name' => 'required',
        'code' => 'required',
        'type_id' => 'required',
        'guid' => 'required',
    ];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

}
