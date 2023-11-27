<?php

namespace App\Controllers\Api\v1;

use App\Controllers\ApiController;
use App\Entities\Feature;
use App\Entities\FeatureValue;
use App\Models\FeatureModel;
use App\Models\FeatureValueModel;

class FeatureController extends ApiController
{
    protected FeatureModel $featureModel;
    protected FeatureValueModel $featureValueModel;

    public function __construct()
    {
        $this->featureModel = model(FeatureModel::class);
        $this->featureValueModel = model(FeatureValueModel::class);
    }

    /**
     * Create a new or update an existing Feature.
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

        $feature = $this->featureModel->findByGuid($guid);
        if ($feature === null) {
            $crud = 'create';
            $feature = new Feature();
        } else {
            $crud = 'update';
        }

        $feature->guid = $guid;
        if (property_exists($inputData, 'name')) {
            $feature->name = trim($inputData->name);
            if ($crud == 'create') {
                $feature->setCode($feature->name);
            }
        }
        if (property_exists($inputData, 'value_type')) {
            $feature->type = $inputData->value_type;
        }
        if (property_exists($inputData, 'reference_type')) {
            $feature->reference = $inputData->reference_type;
        }
        if (property_exists($inputData, 'published')) {
            $feature->published = $inputData->published;
        }

        if ($feature->hasChanged() && ! $this->featureModel->save($feature)) {
            return $this->failValidationErrors($this->featureModel->errors());
        } else {
            return $crud == 'create' ? $this->respondCreated() : $this->respondUpdated();
        }
    }

    /**
     * Create a new or update an existing Feature value.
     *
     * @param string $feature_guid
     * @param string $guid
     *
     * @return mixed
     */
    public function saveValue(string $feature_guid, string $guid)
    {
        $inputData = $this->request->getJSON();

        if (empty($inputData)) {
            return $this->failValidationErrors(lang('RESTful.invalidInput', ['']));
        }

        // Get Feature
        $feature = $this->featureModel->findByGuid($feature_guid);
        if ($feature === null) {
            return $this->failValidationErrors(lang('RESTful.fieldNotFound', ['feature [' . $feature_guid . ']']));
        }

        // Save Feature value
        $featureValue = $this->featureValueModel->findByFeatureGuid($feature->id, $guid);
        if ($featureValue === null) {
            $crud = 'create';
            $featureValue = new FeatureValue();
        } else {
            $crud = 'update';
        }
        $featureValue->reference = 'miscellanea';
        $featureValue->feature_id = $feature->id;
        $featureValue->guid = $guid;
        if (property_exists($inputData, 'value')) {
            $featureValue->value = trim($inputData->value);
            if ($crud == 'create') {
                $featureValue->setCode($featureValue->value);
            }
            $featureValue->value_double = null;
            if (in_array($feature->type, array('double', 'boolean'))) {
                $value_double = str_replace(',','.', $featureValue->value);
                $featureValue->value_double = ! preg_match('~[^0-9\.-]~', $value_double) && is_numeric($value_double) ?
                    (float)$value_double : null;
                if ($featureValue->value_double === null) {
                    return $this->failValidationErrors(lang('RESTful.invalidInput', ['feature [' . $feature_guid . ']: invalid type of value. Double type is required']));
                }
            }
        }
        if (property_exists($inputData, 'description')) {
            $featureValue->description = $inputData->description ? trim($inputData->description) : null;
        }

        if ($featureValue->hasChanged() && ! $this->featureValueModel->save($featureValue)) {
            return $this->failValidationErrors($this->featureValueModel->errors());
        } else {
            return $crud == 'create' ? $this->respondCreated() : $this->respondUpdated();
        }
    }

}
