<?php

namespace App\Controllers\Api\v1;

use App\Controllers\ApiController;
use App\Entities\Color;
use App\Entities\ColorGroup;
use App\Entities\FeatureValue;
use App\Models\ColorGroupModel;
use App\Models\ColorModel;
use App\Models\FeatureModel;
use App\Models\FeatureValueModel;
use App\Models\ProductFeatureValueModel;

class ColorController extends ApiController
{
    protected ColorModel $colorModel;
    protected ColorGroupModel $colorGroupModel;
    protected FeatureModel $featureModel;
    protected FeatureValueModel $featureValueModel;
    protected ProductFeatureValueModel $productFeatureValueModel;

    public function __construct()
    {
        $this->colorModel = model(ColorModel::class);
        $this->colorGroupModel = model(ColorGroupModel::class);
        $this->featureModel = model(FeatureModel::class);
        $this->featureValueModel = model(FeatureValueModel::class);
        $this->productFeatureValueModel  = model(ProductFeatureValueModel::class);
    }

    /**
     * Create a new or update an existing Color.
     *
     * @param string $group_guid
     * @param string $guid
     *
     * @return mixed
     * @throws \ReflectionException
     */
    public function save(string $group_guid, string $guid)
    {
        $inputData = $this->request->getJSON();

        if (empty($inputData)) {
            return $this->failValidationErrors(lang('RESTful.invalidInput', ['']));
        }

        $featureColor = $this->featureModel->findByReference('color');
        if ($featureColor === null) {
            return $this->failValidationErrors(lang('RESTful.fieldNotFound', ['The feature with reference_type=color']));
        }
        $featureColorGroup = $this->featureModel->findByReference('color_group');
        if ($featureColorGroup === null) {
            return $this->failValidationErrors(lang('RESTful.fieldNotFound', ['The feature with reference_type=color_group']));
        }

        $groupFeatureValue = $this->featureValueModel->findByFeatureGuid($featureColorGroup->id, $group_guid);
        if ($groupFeatureValue === null) {
            return $this->failValidationErrors(lang('RESTful.fieldNotFound', ['Color group']));
        }

        // Transaction start
        $this->featureValueModel->db->transStart();

        // Save Feature value
        $featureValue = $this->featureValueModel->findByFeatureGuid($featureColor->id, $guid);
        if ($featureValue === null) {
            $crud = 'create';
            $featureValue = new FeatureValue();
        } else {
            $crud = 'update';
        }
        $featureValue->reference = 'color';
        $featureValue->feature_id = $featureColor->id;
        $featureValue->guid = $guid;
        if (property_exists($inputData, 'name')) {
            $featureValue->value = trim($inputData->name);
            if ($crud == 'create') {
                $featureValue->setCode($featureValue->value);
            }
        }
        if ($featureValue->hasChanged() && ! $this->featureValueModel->save($featureValue)) {
            return $this->failValidationErrors($this->featureValueModel->errors());
        }

        // Save Color
        $featureValueId = $this->featureValueModel->getInsertID() ?: $featureValue->id;
        $color = $this->colorModel->find($featureValueId);
        if ($color === null) {
            $color = new Color();
            $color->id = $featureValueId;
        } else {
            // If the color group has changed, then change all relevant product features
            if ($groupFeatureValue->id != $color->group_id) {
                $this->productFeatureValueModel->builder()
                    ->where(['feature_id' => $featureColorGroup->id, 'value_id' => $color->group_id])
                    ->update(['value_id' => $groupFeatureValue->id]);
            }
        }
        $color->group_id = $groupFeatureValue->id;
        $color->name = $featureValue->value;
        if (property_exists($inputData, 'description')) {
            $color->description = trim($inputData->description);
        }
        if ($color->hasChanged() && ! $this->colorModel->save($color)) {
            return $this->failValidationErrors($this->colorModel->errors());
        }

        // Transaction commit
        $this->featureValueModel->db->transCommit();

        return $crud == 'create' ? $this->respondCreated() : $this->respondUpdated();
    }


    /**
     * Create a new or update an existing Color group.
     *
     * @param string $guid
     *
     * @return mixed
     */
    public function saveGroup(string $guid)
    {
        $inputData = $this->request->getJSON();

        if (empty($inputData)) {
            return $this->failValidationErrors(lang('RESTful.invalidInput', ['']));
        }

        $featureColorGroup = $this->featureModel->findByReference('color_group');
        if ($featureColorGroup === null) {
            return $this->failValidationErrors(lang('RESTful.fieldNotFound', ['The feature with reference_type=color_group']));
        }

        // Transaction start
        $this->featureValueModel->db->transStart();

        // Save Feature value
        $featureValue = $this->featureValueModel->findByFeatureGuid($featureColorGroup->id, $guid);
        if ($featureValue === null) {
            $crud = 'create';
            $featureValue = new FeatureValue();
        } else {
            $crud = 'update';
        }
        $featureValue->reference = 'color_group';
        $featureValue->feature_id = $featureColorGroup->id;
        $featureValue->guid = $guid;
        if (property_exists($inputData, 'name')) {
            $featureValue->value = trim($inputData->name);
            if ($crud == 'create') {
                $featureValue->setCode($featureValue->value);
            }
        }
        if ($featureValue->hasChanged() && ! $this->featureValueModel->save($featureValue)) {
            return $this->failValidationErrors($this->featureValueModel->errors());
        }

        // Save Color group
        $featureValueId = $this->featureValueModel->getInsertID() ?: $featureValue->id;
        $colorGroup = $this->colorGroupModel->find($featureValueId);
        if ($colorGroup === null) {
            $colorGroup = new ColorGroup();
            $colorGroup->id = $featureValueId;
        }
        $colorGroup->name = $featureValue->value;
        if ($colorGroup->hasChanged() && ! $this->colorGroupModel->save($colorGroup)) {
            return $this->failValidationErrors($this->colorGroupModel->errors());
        }

        // Transaction commit
        $this->featureValueModel->db->transCommit();

        return $crud == 'create' ? $this->respondCreated() : $this->respondUpdated();
    }


}
