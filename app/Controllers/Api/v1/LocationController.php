<?php

namespace App\Controllers\Api\v1;

use App\Controllers\ApiController;
use App\Models\GeoipLocationModel;

class LocationController extends ApiController
{
    protected GeoipLocationModel $geoipLocationModel;

    public function __construct()
    {
        $this->geoipLocationModel  = model(GeoipLocationModel::class);
    }

    /**
     * Search for Locations by filter.
     *
     * @return mixed
     */
    public function get()
    {
        $productTypes = $this->geoipLocationModel->query(
            ['published' => true, 'type' => 'city'],
            ['sort' => 'asc', 'name' => 'asc'],
            []
        )->findItems();

        return $this->respondUpdated($productTypes);
    }

}
