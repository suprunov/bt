<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class Model extends Entity
{
    protected $datamap = [];
    protected $dates   = [];
    protected $casts = [
        'type'          => 'json',
        'aliases'       => 'json',
        'brand'         => 'json',
        'colors'        => 'json',
        'review_totals' => 'json',
    ];

    public function getPictures(): array
    {
        $pictures = json_decode($this->attributes['pictures']) ?? [];
        $colors   = json_decode($this->attributes['colors']) ?? [];

        $picturesFrm = [];
        foreach ($pictures as $picture) {
            $pictureId   = $picture->id;
            $colorId     = $picture->color->id ?? null;
            $colorName   = $picture->color->name ?? 'default';
            if (! isset($picturesFrm[$colorId])) {
                $picturesFrm[$colorId] = (object)[
                    'color'   => (object)['id' => $colorId, 'name' => $colorName],
                    'items'   => [],
                    'sort'    => substr($picture->sort, 0,-4),
                ];
            }
            if (! isset($picturesFrm[$colorId]->items[$pictureId])) {
                $picturesFrm[$colorId]->items[$pictureId] = (object)[
                    'id'         => $pictureId,
                    'sort'       => $picture->sort,
                    'variations' => new \stdClass()
                ];
            }
            unset($picture->id, $picture->sort, $picture->color);
            $picturesFrm[$colorId]->items[$pictureId]->variations->{$picture->variation_code} = $picture;
        }

        // Sort by color
        usort($picturesFrm, fn($a, $b) => (int)$a->sort - (int)$b->sort);

        // Add other existing colors for this model
        foreach ($colors as $color) {
            if (! isset($picturesFrm[$color->id])) {
                $picturesFrm[$color->id] = (object)[
                    'color'   => (object)['id' => $color->id, 'name' => $color->name],
                    'items'   => [],
                ];
            }
        }

        // Clean keys
        foreach ($picturesFrm as &$color) {
            $color->items = array_values($color->items);
        }
        unset($color);

        return array_values($picturesFrm);
    }

    public function getUri(): string
    {
        return "model/{$this->attributes['code']}-{$this->attributes['id']}/";
    }
}
