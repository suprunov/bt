<?php

namespace App\Models;

use App\Entities\Picture;

class PictureModel extends BaseModel
{
    protected $table            = 'pictures';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = Picture::class;
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'type_id',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // Validation
    protected $validationRules      = [
        'type_id' => 'required',
    ];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

}
