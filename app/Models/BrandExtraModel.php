<?php

namespace App\Models;

use App\Entities\BrandExtra;

class BrandExtraModel extends BaseModel
{
    protected $table            = 'brands_extras';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = false;
    protected $returnType       = BrandExtra::class;
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'id',
        'description',
        'aliases',
    ];

    // Dates
    protected $useTimestamps = false;

    // Validation
    protected $validationRules      = [
        'id' => 'required',
    ];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

}
