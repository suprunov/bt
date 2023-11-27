<?php

namespace App\Controllers\Api\v1;

use App\Controllers\ApiController;
use App\Entities\Delivery;
use App\Models\DeliveryModel;

class DeliveryController extends ApiController
{
    protected DeliveryModel $deliveryModel;

    public function __construct()
    {
        $this->deliveryModel = model(DeliveryModel::class);
    }

    /**
     * Create a new or update an existing Delivery.
     *
     * @param string $guid
     *
     * @return mixed
     * @throws \ReflectionException
     */
    public function save(string $guid)
    {
        $inputData = $this->request->getJSON();

        if (empty($inputData)) {
            return $this->failValidationErrors(lang('RESTful.invalidInput', ['']));
        }

        // Save Delivery
        $delivery = $this->deliveryModel->findByGuid($guid);
        if ($delivery === null) {
            $crud = 'create';
            $delivery = new Delivery();
        } else {
            $crud = 'update';
        }

        $delivery->guid = $guid;
        if (property_exists($inputData, 'name')) {
            $delivery->name = trim($inputData->name);
        }
        if (property_exists($inputData, 'type')) {
            $delivery->type = $inputData->type;
        }
        if (property_exists($inputData, 'code')) {
            $delivery->code = $inputData->code;
        }
        if (property_exists($inputData, 'location_wsid')) {
            $delivery->location_id = $inputData->location_wsid;
        }
        if (property_exists($inputData, 'interval')) {
            $delivery->interval = $inputData->interval;
        }
        if (property_exists($inputData, 'description')) {
            $delivery->description = $inputData->description;
        }
        if (property_exists($inputData, 'price')) {
            $delivery->price = $inputData->price;
        }
        if (property_exists($inputData, 'sort')) {
            $delivery->sort = $inputData->sort;
        }
        if (property_exists($inputData, 'published')) {
            $delivery->published = $inputData->published;
        }

        if ($delivery->hasChanged() && ! $this->deliveryModel->save($delivery)) {
            return $this->failValidationErrors($this->deliveryModel->errors());
        }

        return $crud == 'create' ? $this->respondCreated() : $this->respondUpdated();
    }
}
