<?php

namespace App\Controllers\Api\V1;

use App\Controllers\ApiController;
use App\Entities\DeliveryPeriod;
use App\Entities\DeliverySchedule;
use App\Entities\Storage;
use App\Models\DeliveryModel;
use App\Models\DeliveryPeriodModel;
use App\Models\DeliveryScheduleModel;
use App\Models\StorageLocationModel;
use App\Models\StorageModel;

class StorageController extends ApiController
{
    protected StorageModel $storageModel;
    protected StorageLocationModel $storageLocationModel;
    protected DeliveryModel $deliveryModel;
    protected DeliveryPeriodModel $deliveryPeriodModel;
    protected DeliveryScheduleModel $deliveryScheduleModel;

    public function __construct()
    {
        $this->storageModel          = model(StorageModel::class);
        $this->storageLocationModel  = model(StorageLocationModel::class);

        $this->deliveryModel         = model(DeliveryModel::class);
        $this->deliveryPeriodModel   = model(DeliveryPeriodModel::class);
        $this->deliveryScheduleModel = model(DeliveryScheduleModel::class);
    }

    /**
     * Create a new or update an existing Storage.
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

        // Transaction start
        $this->storageModel->db->transStart();

        $storage = $this->storageModel->findByGuid($guid);
        if ($storage === null) {
            $crud = 'create';
            $storage = new Storage();
        } else {
            $crud = 'update';
        }

        $storage->guid = $guid;
        if (property_exists($inputData, 'name')) {
            $storage->name = trim($inputData->name);
            if ($crud == 'create') {
                $storage->setCode($storage->name);
            }
        }
        if (property_exists($inputData, 'type')) {
            $storage->type = $inputData->type;
        }
        if (property_exists($inputData, 'api')) {
            $storage->api = $inputData->api;
        }

        if ($storage->hasChanged() && ! $this->storageModel->save($storage)) {
            return $this->failValidationErrors($this->storageModel->errors());
        }
        $storage->id = $storage->id ?: $this->storageModel->getInsertID();

        // Save Storage locations
        $storageLocations = [];
        if (property_exists($inputData, 'locations') && count($inputData->locations)) {
            foreach ($inputData->locations as $item) {
                $storageLocations[] = ['storage_id' => $storage->id, 'location_id' => $item->location_wsid];
            }
        }
        $this->storageLocationModel->where('storage_id', $storage->id)->delete();
        if (count($storageLocations)) {
            if ($this->storageLocationModel->insertBatch($storageLocations) === false) {
                return $this->failValidationErrors($this->storageLocationModel->errors());
            }
        }

        // Transaction commit
        $this->storageModel->db->transCommit();

        return $crud == 'create' ? $this->respondCreated() : $this->respondUpdated();
    }

    public function saveDeliverySchedules(string $storageGuid, int $locationId)
    {
        $inputData = $this->request->getJSON();

        if (empty($inputData)) {
            return $this->failValidationErrors(lang('RESTful.invalidInput', ['']));
        }

        $storage = $this->storageModel->findByGuid($storageGuid);
        if ($storage === null) {
            return $this->failValidationErrors(lang('RESTful.fieldNotFound', ['Storage [' . $storageGuid . ']' ]));
        }

        // Transaction start
        $this->storageModel->db->transStart();

        // Clean periods and schedules.
        $this->deliveryPeriodModel->where('storage_id', $storage->id)->where('location_id', $locationId)->delete();

        // Save periods and schedules.
        foreach ($inputData as $weekdayData)
        {
            foreach ($weekdayData->periods as $periodData)
            {
                // Save a period.
                $period = new DeliveryPeriod();
                $period->fill([
                    'storage_id'  => $storage->id,
                    'location_id' => $locationId,
                    'week_day'    => $weekdayData->week_day,
                    'time_from'   => $periodData->time_from,
                    'time_to'     => $periodData->time_to,
                ]);
                if (! $this->deliveryPeriodModel->save($period)) {
                    return $this->failValidationErrors($this->deliveryPeriodModel->errors());
                }
                $period->id = $this->deliveryPeriodModel->getInsertID();

                // Save a schedules.
                $schedules = [];
                foreach ($periodData->receiving as $receiving)
                {
                    // Find a delivery or a pickup storage.
                    if ($receiving->type == 'delivery') {
                        $delivery = $this->deliveryModel->findByGuid($receiving->guid);
                    } else {
                        $delivery = $this->storageModel->findByGuid($receiving->guid);
                    }
                    if ($delivery === null) {
                        return $this->failValidationErrors(lang('RESTful.fieldNotFound', ['Receiving [' . $receiving->guid . ']' ]));
                    }

                    $schedule = new DeliverySchedule();
                    $schedule->fill([
                        'period_id'            => $period->id,
                        'delivery_type'        => $receiving->type,
                        'delivery_id'          => $delivery->id,
                        'delivery_days'        => $receiving->days,
                        'delivery_time_from'   => $receiving->time_from,
                    ]);
                    $schedules[] = $schedule;
                }
                if (count($schedules)) {
                    if ($this->deliveryScheduleModel->insertBatch($schedules) === false) {
                        return $this->failValidationErrors($this->deliveryScheduleModel->errors());
                    }
                }
            }
        }

        // Transaction commit
        $this->storageModel->db->transCommit();

        return $this->respondUpdated();
    }
}
