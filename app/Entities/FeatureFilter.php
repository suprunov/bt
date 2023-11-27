<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class FeatureFilter extends Entity
{
    protected $datamap = [];
    protected $dates   = [];
    protected $casts   = [
        'feature' => 'json',
    ];
}
