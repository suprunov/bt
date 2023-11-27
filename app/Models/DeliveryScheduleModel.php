<?php

namespace App\Models;

use App\Entities\DeliverySchedule;

class DeliveryScheduleModel extends BaseModel
{
    protected $table            = 'deliveries_schedules';
    protected $primaryKey       = 'period_id'; // It's a trick. This table doesn't have a primary key.
    protected $useAutoIncrement = false;
    protected $returnType       = DeliverySchedule::class;
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'period_id',
        'delivery_type',
        'delivery_id',
        'delivery_days',
        'delivery_time_from',
    ];

    // Dates
    protected $useTimestamps = false;

    // Validation
    protected $validationRules      = [
        'period_id'           => 'required',
        'delivery_type'       => 'required',
        'delivery_id'         => 'required',
        'delivery_days'       => 'required',
        'delivery_time_from'  => 'required|regex_match[/^(0[0-9]|1[0-9]|2[0-3]):[0-5][0-9]$/]',
    ];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

}
