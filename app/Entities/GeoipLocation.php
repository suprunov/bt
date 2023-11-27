<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class GeoipLocation extends Entity
{
    protected $datamap = [];
    protected $dates   = [];
    protected $casts   = [
        'name' => 'json',
    ];
}
