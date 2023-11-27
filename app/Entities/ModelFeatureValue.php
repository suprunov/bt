<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class ModelFeatureValue extends Entity
{
    protected $datamap = [];
    protected $dates   = [];
    protected $casts   = [
        'feature' => 'json',
        'value'   => 'json',
    ];
}
