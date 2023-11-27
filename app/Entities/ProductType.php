<?php

namespace App\Entities;

use App\Entities\Traits\SpecialFields;
use CodeIgniter\Entity\Entity;

class ProductType extends Entity
{
    use SpecialFields;

    protected $datamap = [];
    protected $dates   = [];
    protected $casts   = [
        'name' => 'json'
    ];
}
