<?php

namespace App\Models;

use App\Entities\Client;

class ClientModel extends BaseModel
{
    protected $table            = 'clients';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = Client::class;
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'legal_status',
        'price_id',
        'blocked',
        'company',
        'inn',
        'kpp',
        'okpo',
        'guid',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // Validation
    protected $validationRules      = [
        'legal_status' => 'required',
        'price_id' => 'required',
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

    /**
     * Get user client data.
     *
     * @param int $user_id
     * @param int $client_id
     * @return object|null
     */
    public function findUserClient(int $user_id, int $client_id): object|null
    {
        return $this->select("{$this->table}.*, clients_users.main, clients_users.active")
            ->join('clients_users', "clients_users.client_id = {$this->table}.id")
            ->where("{$this->table}.id", $client_id)
            ->where('clients_users.user_id', $user_id)
            ->get()
            ->getFirstRow('object');
    }
}
