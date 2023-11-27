<?php

namespace App\Models;

use App\Entities\DeliveryPeriod;

class DeliveryPeriodModel extends BaseModel
{
    protected $table            = 'deliveries_periods';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = DeliveryPeriod::class;
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'storage_id',
        'location_id',
        'week_day',
        'time_from',
        'time_to',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = false;

    // Validation
    protected $validationRules      = [
        'storage_id'        => 'required',
        'location_id'       => 'required',
        'week_day'          => 'required|in_list[1,2,3,4,5,6,7]',
        'time_from'         => 'required|regex_match[/^(0[0-9]|1[0-9]|2[0-3]):[0-5][0-9]$/]',
        'time_to'           => 'required|regex_match[/^(0[0-9]|1[0-9]|2[0-3]):[0-5][0-9]$/]',
    ];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

}
