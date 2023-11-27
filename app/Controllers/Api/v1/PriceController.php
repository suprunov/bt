<?php

namespace App\Controllers\Api\V1;

use App\Controllers\ApiController;
use App\Entities\Price;
use App\Models\PriceModel;

class PriceController extends ApiController
{
    protected PriceModel $priceModel;

    public function __construct()
    {
        $this->priceModel = model(PriceModel::class);
    }

    /**
     * Create a new or update an existing Price.
     *
     * @param string $guid
     *
     * @return mixed
     */
    public function save(string $guid)
    {
        $inputData = $this->request->getJSON();

        if (empty($inputData)) {
            return $this->failValidationErrors(lang('RESTful.invalidInput', ['']));
        }

        $price = $this->priceModel->findByGuid($guid);
        if ($price === null) {
            $crud = 'create';
            $price = new Price;
        } else {
            $crud = 'update';
        }

        // TODO add location_id existing check - when there will be location import method

        $price->guid = $guid;
        if (property_exists($inputData, 'location_wsid')) {
            $price->location_id = $inputData->location_wsid;
        }
        if (property_exists($inputData, 'name')) {
            $price->name = trim($inputData->name);
            if ($crud == 'create') {
                $price->setCode($price->name);
            }
        }
        if (property_exists($inputData, 'default')) {
            $price->default = $inputData->default;
        }
        if (property_exists($inputData, 'available')) {
            $price->available = $inputData->available;
        }
        if (property_exists($inputData, 'type_id')) {
            $price->type_id = $inputData->type_id;
        }

        if ($price->hasChanged() && ! $this->priceModel->save($price)) {
            return $this->failValidationErrors($this->priceModel->errors());
        } else {
            return $crud == 'create' ? $this->respondCreated() : $this->respondUpdated();
        }
    }
}
