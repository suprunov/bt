<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;
use App\Entities\Traits\SpecialFields;

class Price extends Entity
{
    use SpecialFields;

    protected $datamap = [];
    protected $dates   = [];
    protected $casts   = [];
}
