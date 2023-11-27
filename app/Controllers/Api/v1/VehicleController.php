<?php

namespace App\Controllers\Api\v1;

use App\Controllers\ApiController;
use App\Entities\VehicleBrand;
use App\Entities\VehicleModel;
use App\Entities\VehicleModification;
use App\Entities\FeatureValue;
use App\Models\PictureModel;
use App\Models\VehicleBrandModel;
use App\Models\VehicleModelModel;
use App\Models\VehicleModificationModel;
use App\Models\FeatureModel;
use App\Models\FeatureValueModel;

class VehicleController extends ApiController
{
    protected VehicleBrandModel $vehicleBrandModel;
    protected FeatureModel $featureModel;
    protected FeatureValueModel $featureValueModel;
    protected VehicleModelModel $vehicleModelModel;
    protected VehicleModificationModel $vehicleModificationModel;
    protected PictureModel $pictureModel;

    public function __construct()
    {
        $this->vehicleBrandModel = model(VehicleBrandModel::class);
        $this->featureModel = model(FeatureModel::class);
        $this->featureValueModel = model(FeatureValueModel::class);
        $this->vehicleModelModel = model(VehicleModelModel::class);
        $this->vehicleModificationModel = model(VehicleModificationModel::class);
        $this->pictureModel = model(PictureModel::class);
    }

    /**
     * Create a new or update an existing Vehicle brand.
     *
     * @param string $guid
     *
     * @return mixed
     */
    public function saveBrand(string $guid)
    {
        $inputData = $this->request->getJSON();

        if (empty($inputData)) {
            return $this->failValidationErrors(lang('RESTful.invalidInput', ['']));
        }

        $featureBrand = $this->featureModel->findByReference('car_brand');
        if ($featureBrand === null) {
            return $this->failValidationErrors(lang('RESTful.fieldNotFound', ['The feature with reference_type=car_brand']));
        }

        // Transaction start
        $this->featureValueModel->db->transStart();

        // Save Feature value
        $featureValue = $this->featureValueModel->findByFeatureGuid($featureBrand->id, $guid);
        if ($featureValue === null) {
            $crud = 'create';
            $featureValue = new FeatureValue();
        } else {
            $crud = 'update';
        }
        $featureValue->reference = 'car_brand';
        $featureValue->feature_id = $featureBrand->id;
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
        $featureValue->id = $featureValue->id ?: $this->featureValueModel->getInsertID();

        // Save Vehicle brand
        $brand = $this->vehicleBrandModel->find($featureValue->id);
        if ($brand === null) {
            $brand = new VehicleBrand();
            $brand->id = $featureValue->id;
        }
        $brand->name = $featureValue->value;
        if (property_exists($inputData, 'aliases')) {
            $brand->aliases = is_array($inputData->aliases) && count($inputData->aliases) ?
                json_encode($inputData->aliases) : null;
        }
        if (property_exists($inputData, 'published')) {
            $brand->published = $inputData->published;
        }

        // TEMP TODO import must have this field
        if (! $brand->type) {
            $brand->type = 'car';
        }

        if ($brand->hasChanged() && ! $this->vehicleBrandModel->save($brand)) {
            return $this->failValidationErrors($this->vehicleBrandModel->errors());
        }

        // Transaction commit
        $this->featureValueModel->db->transCommit();

        return $crud == 'create' ? $this->respondCreated() : $this->respondUpdated();
    }

    /**
     *  Update an existing Brand.
     *
     * @param int|string $id
     *
     * @return mixed
     * @throws \ReflectionException
     */
    public function saveBrandById(int|string $id)
    {
        $inputData = $this->request->getJSON();
        if (empty($inputData)) {
            return $this->failValidationErrors(lang('RESTful.invalidInput', ['']));
        }

        $brand = $this->vehicleBrandModel->find($id);
        if ($brand === null) {
            return $this->respond(null, $this->codes['resource_not_found']);
        }

        // Transaction start
        $this->vehicleBrandModel->db->transStart();

        // Save Brand.
        if (property_exists($inputData, 'picture')) {
            $pictureId = $inputData->picture->id ?? null;
            if ($this->pictureModel->find($pictureId) === null) {
                return $this->respond(null, $this->codes['resource_not_found']);
            }
            $brand->picture_id = $pictureId;
        }
        if (property_exists($inputData, 'description')) {
            $brand->description = $inputData->description;
        }
        if ($brand->hasChanged() && ! $this->vehicleBrandModel->save($brand)) {
            return $this->failValidationErrors($this->vehicleBrandModel->errors());
        }

        // Transaction commit
        $this->vehicleBrandModel->db->transCommit();

        return $this->respondUpdated(['result' => true]);
    }

    /**
     * Create a new or update an existing Vehicle model.
     *
     * @param string $brandGuid
     * @param string $modelGuid
     *
     * @return mixed
     */
    public function saveModel(string $brandGuid, string $modelGuid)
    {
        $inputData = $this->request->getJSON();

        if (empty($inputData)) {
            return $this->failValidationErrors(lang('RESTful.invalidInput', ['']));
        }

        $featureBrand = $this->featureModel->findByReference('car_brand');
        if ($featureBrand === null) {
            return $this->failValidationErrors(lang('RESTful.fieldNotFound', ['The feature with reference_type=car_brand']));
        }
        $featureBrandValue = $this->featureValueModel->findByFeatureGuid($featureBrand->id, $brandGuid);
        if ($featureBrandValue === null) {
            return $this->failValidationErrors(lang('RESTful.fieldNotFound', ['Car brand [' . $brandGuid .']']));
        }

        // Save Vehicle model
        $vehicleModel = $this->vehicleModelModel->findByGuid($modelGuid);
        if ($vehicleModel === null) {
            $crud = 'create';
            $vehicleModel = new VehicleModel();
        } else {
            $crud = 'update';
        }
        $vehicleModel->brand_id = $featureBrandValue->id;
        $vehicleModel->guid = $modelGuid;
        if (property_exists($inputData, 'name')) {
            $vehicleModel->name = trim($inputData->name);
            if ($crud == 'create') {
                $vehicleModel->setCode($vehicleModel->name);
            }
        }
        if (property_exists($inputData, 'name_rus')) {
            $vehicleModel->name_rus = $inputData->name_rus;
        }
        if (property_exists($inputData, 'aliases')) {
            $vehicleModel->aliases = is_array($inputData->aliases) && count($inputData->aliases) ?
                json_encode($inputData->aliases) : null;
        }
        if (property_exists($inputData, 'published')) {
            $vehicleModel->published = $inputData->published;
        }

        if ($vehicleModel->hasChanged() && ! $this->vehicleModelModel->save($vehicleModel)) {
            return $this->failValidationErrors($this->vehicleModelModel->errors());
        }

        return $crud == 'create' ? $this->respondCreated() : $this->respondUpdated();
    }

    /**
     * Create a new or update an existing Vehicle modification.
     *
     * @param string $modelGuid
     * @param string $modificationGuid
     *
     * @return mixed
     */
    public function saveModification(string $modelGuid, string $modificationGuid)
    {
        $inputData = $this->request->getJSON();

        if (empty($inputData)) {
            return $this->failValidationErrors(lang('RESTful.invalidInput', ['']));
        }

        $vehicleModel = $this->vehicleModelModel->findByGuid($modelGuid);
        if ($vehicleModel === null) {
            return $this->failValidationErrors(lang('RESTful.fieldNotFound', ['Car model [' . $modelGuid .']']));
        }

        // Save Vehicle modification
        $vehicleModification = $this->vehicleModificationModel->findByGuid($modificationGuid);
        if ($vehicleModification === null) {
            $crud = 'create';
            $vehicleModification = new VehicleModification();
        } else {
            $crud = 'update';
        }
        $vehicleModification->model_id = $vehicleModel->id;
        $vehicleModification->guid = $modificationGuid;
        if (property_exists($inputData, 'name')) {
            $vehicleModification->name = trim($inputData->name);
            if ($crud == 'create') {
                $vehicleModification->setCode($vehicleModification->name);
            }
        }
        if (property_exists($inputData, 'body')) {
            $vehicleModification->body = trim($inputData->body);
        }
        if (property_exists($inputData, 'year_from')) {
            $vehicleModification->year_from = $inputData->year_from;
        }
        if (property_exists($inputData, 'year_to')) {
            $vehicleModification->year_to = $inputData->year_to;
        }
        if (property_exists($inputData, 'published')) {
            $vehicleModification->published = $inputData->published;
        }

        if ($vehicleModification->hasChanged() && ! $this->vehicleModificationModel->save($vehicleModification)) {
            return $this->failValidationErrors($this->vehicleModificationModel->errors());
        }

        return $crud == 'create' ? $this->respondCreated() : $this->respondUpdated();
    }

    /**
     * Search for Brands by filter.
     *
     * @return mixed
     */
    public function get()
    {
        $inputData = $this->request->getJSON();

        // limit
        $limitStep =  25; // TODO
        $page = $inputData->page ?? 1;
        $limitFrom = (($page) - 1) * $limitStep;

        // sort
        $sort = [];
        if (isset($inputData->sort)) {
            list($sortField, $sortDirection) =  explode( '-', $inputData->sort);
            $sort = [$sortField => $sortDirection];
        }

        // filter & fields
        $filter = [];
        $fields = ['pictures'];
        if ($inputData->filter ?? false) {
            if ($inputData->filter->published ?? false) {
                $filter['published'] = true;
            }
            if ($inputData->filter->search_query ?? false) {
                $filter['searchQuery'] = $inputData->filter->search_query;
            }
        }

        $vehicleBrands = $this->vehicleBrandModel->query(
            $filter,
            $sort,
            [$limitFrom => $limitStep],
            ['hints' => ['calcRows' => true]],
            $fields
        )->findItems();

        $result = [
            'data' => $vehicleBrands,
            'pager' => [
                'current' => $page,
                'total'   => $this->vehicleBrandModel->foundRows(), // ceil($this->vehicleBrandModel->foundRows() / $limitStep),
                'per_page'=> $limitStep,
            ]
        ];
        return $this->respondUpdated($result);
    }

    /**
     * Get Brand by ID.
     *
     * @param int|string $id
     *
     * @return mixed
     */
    public function getById(int|string $id)
    {
        $brand = $this->vehicleBrandModel->query(filter:['id' => $id], fields:['pictures', 'description'])->first();
        if ($brand === null)
            return $this->respond(null, $this->codes['resource_not_found']);
        else
            return $this->respondUpdated($brand->toArray());
    }

}
