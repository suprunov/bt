<?php

namespace App\Models;

use App\Entities\Delivery;

class DeliveryModel extends BaseModel
{
    protected $table            = 'deliveries';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = Delivery::class;
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'name',
        'code',
        'type',
        'published',
        'location_id',
        'zone_id',
        'price',
        'interval',
        'week_days',
        'sort',
        'description',
        'guid',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // Validation
    protected $validationRules      = [
        'name'        => 'required',
        'code'        => 'required',
        'type'        => 'required|in_list[tc,to_client]',
        'location_id' => 'required',
        'price'       => 'required',
        'guid'        => 'required',
    ];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

}
