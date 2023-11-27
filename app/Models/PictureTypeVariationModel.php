<?php

namespace App\Models;

use App\Entities\PictureTypeVariation;

class PictureTypeVariationModel extends BaseModel
{
    protected $table            = 'pictures_types_variations';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = PictureTypeVariation::class;
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'type_id',
        'code',
        'watermark',
        'width',
        'height',
        'extension',
    ];

    // Dates
    protected $useTimestamps = false;

    // Validation
    protected $validationRules = [
        'type_id' => 'required',
        'code'    => 'required',
    ];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

}
