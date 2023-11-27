<?php

namespace App\Models;

use App\Entities\ClientStorageContract;

class ClientStorageContractModel extends BaseModel
{
    protected $table            = 'clients_storage_contracts';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = ClientStorageContract::class;
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'number',
        'date',
        'amount',
        'status',
        'storage_period',
        'pickup_in_id',
        'pickup_out_id',
        'pickup_date',
        'pickup_plan_date',
        'pickup_real_date',
        'client_car_id',
        'products',
        'guid',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // Validation
    protected $validationRules      = [
        'number' => 'required',
        'date' => 'required|valid_date[Y-m-d H:i:s]',
        'amount' => 'required|integer',
        'status' => 'required|integer',
        'storage_period' => 'required|integer',
        'pickup_in_id' => 'required',
        'products' => 'required|valid_json',
        'pickup_date' => 'valid_date[Y-m-d]',
        'pickup_plan_date' => 'valid_date[Y-m-d]',
        'pickup_real_date' => 'valid_date[Y-m-d]',
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
}
