<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class Brand extends Entity
{
    protected $datamap = [];
    protected $dates   = [];
    protected $casts   = [
        'name'    => 'json',
        'country' => 'json',
        'type'    => 'json',
        'aliases' => 'json',
    ];

    public function getPicture(): object|null
    {
        $pictures = json_decode($this->attributes['picture']) ?? [];
        $picturesFrm = [];
        foreach ($pictures as $picture) {
            $pictureId = $picture->id;
            if (! isset($picturesFrm[$pictureId]))
                $picturesFrm[$pictureId] = (object)['id' => $pictureId, 'sort' => $picture->sort, 'variations' => new \stdClass()];
            unset($picture->id, $picture->sort);
            $picturesFrm[$pictureId]->variations->{$picture->variation_code} = $picture;
        }
        return array_pop($picturesFrm);
    }

}
