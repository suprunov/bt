<?php

namespace App\Models;

use App\Entities\Storage;

class StorageModel extends BaseModel
{
    protected $table            = 'storages';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = Storage::class;
    protected $useSoftDeletes   = false;
    protected $protectFields    = false;
    protected $allowedFields    = [
        'name',
        'code',
        'type',
        'api',
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
        'type' => 'required|in_list[ours,vendor,bailment]',
        'guid' => 'required',
    ];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

}
