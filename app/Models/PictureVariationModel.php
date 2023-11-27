<?php

namespace App\Models;

use App\Entities\PictureVariation;

class PictureVariationModel extends BaseModel
{
    protected $table            = 'pictures_variations';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = PictureVariation::class;
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'picture_id',
        'type_variation_id',
        'path',
        'filename',
        'extension',
        'watermark',
        'source',
        'width',
        'height',
        'size',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // Validation
    protected $validationRules = [
        'picture_id'        => 'required',
        'type_variation_id' => 'required',
        'path'              => 'required',
        'filename'          => 'required',
        'extension'         => 'required',
    ];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

}
