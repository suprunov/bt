<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class UserContactPerson extends Entity
{
    protected $datamap = [];
    protected $dates   = [];
    protected $casts   = [];

    public function getName()
    {
        return trim($this->attributes['last_name'] . ' ' . $this->attributes['first_name']);
    }
}
