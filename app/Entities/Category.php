<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;
use App\Entities\Traits\SpecialFields;

class Category extends Entity
{
    use SpecialFields;

    protected $datamap = [];
    protected $dates   = ['created_at', 'updated_at'];
    protected $casts   = [];
}
