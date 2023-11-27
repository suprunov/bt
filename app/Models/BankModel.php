<?php

namespace App\Models;

use App\Entities\Bank;

class BankModel extends BaseModel
{
    protected $table            = 'banks';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = Bank::class;
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'name',
        'bic',
        'corresponding_account',
        'active',
        'guid',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // Validation
    protected $validationRules = [
        'name'                  => 'required',
        'bic'                   => 'required',
        'corresponding_account' => 'required',
        'guid'                  => 'required',
    ];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

}
