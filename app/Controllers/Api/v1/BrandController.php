<?php

namespace App\Controllers\Api\v1;

use App\Controllers\ApiController;
use App\Entities\Brand;
use App\Entities\BrandExtra;
use App\Entities\Country;
use App\Entities\FeatureValue;
use App\Models\BrandExtraModel;
use App\Models\BrandModel;
use App\Models\CountryModel;
use App\Models\FeatureModel;
use App\Models\FeatureValueModel;
use App\Models\PictureModel;

class BrandController extends ApiController
{
    protected BrandModel $brandModel;
    protected BrandExtraModel $brandExtraModel;
    protected CountryModel $countryModel;
    protected FeatureModel $featureModel;
    protected FeatureValueModel $featureValueModel;
    protected PictureModel $pictureModel;

    public function __construct()
    {
        $this->brandModel = model(BrandModel::class);
        $this->brandExtraModel = model(BrandExtraModel::class);
        $this->countryModel = model(CountryModel::class);
        $this->featureModel = model(FeatureModel::class);
        $this->featureValueModel = model(FeatureValueModel::class);
        $this->pictureModel = model(PictureModel::class);
    }

    /**
     * Create a new or update an existing Brand.
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

        $featureBrand = $this->featureModel->findByReference('brand');
        if ($featureBrand === null) {
            return $this->failValidationErrors(lang('RESTful.fieldNotFound', ['The feature with reference_type=brand']));
        }
        $featureCountry = $this->featureModel->findByReference('country');
        if ($featureCountry === null) {
            return $this->failValidationErrors(lang('RESTful.fieldNotFound', ['The feature with reference_type=country']));
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
        $featureValue->reference = 'brand';
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

        // Save Brand
        $featureValueId = $this->featureValueModel->getInsertID() ?: $featureValue->id;
        $brand = $this->brandModel->find($featureValueId);
        if ($brand === null) {
            $brand = new Brand();
            $brand->id = $featureValueId;
        }
        $brand->name = $featureValue->value;
        if (property_exists($inputData, 'name_eng')) {
            $brand->name_eng = $inputData->name_eng;
        }
        if (property_exists($inputData, 'name_rus')) {
            $brand->name_rus = $inputData->name_rus;
        }
        if (property_exists($inputData, 'published')) {
            $brand->published = $inputData->published;
        }

        // Get Country - 1C doesn't have such a directory, so we create it on our side
        if (property_exists($inputData, 'country')) {
            $inputData->country = trim($inputData->country);
            if ($inputData->country) {

                $countryFeatureValue = $this->featureValueModel->findByFeatureValue($featureCountry->id, $inputData->country);
                // Save Country
                if ($countryFeatureValue === null) {
                    // Save value
                    $countryFeatureValue = new FeatureValue();
                    $countryFeatureValue->reference = 'country';
                    $countryFeatureValue->feature_id = $featureCountry->id;
                    $countryFeatureValue->value = $inputData->country;
                    $countryFeatureValue->setCode($inputData->country);
                    if (! $this->featureValueModel->save($countryFeatureValue)) {
                        return $this->failValidationErrors($this->featureValueModel->errors());
                    }
                    $countryFeatureValue->id = $this->featureValueModel->getInsertID();
                    // Save reference value
                    $country = new Country();
                    $country->id = $countryFeatureValue->id;
                    $country->name = $inputData->country;
                    if (property_exists($inputData, 'country_plural')) {
                        $country->name_plural = $inputData->country_plural;
                    }
                    if (! $this->countryModel->save($country)) {
                        return $this->failValidationErrors($this->countryModel->errors());
                    }
                }
            }
            $brand->country_id = $countryFeatureValue->id ?? null;
        }

        if ($brand->hasChanged() && ! $this->brandModel->save($brand)) {
            return $this->failValidationErrors($this->brandModel->errors());
        }

        // Save Brand extra data
        if (property_exists($inputData, 'aliases')) {
            $brandExtra = $this->brandExtraModel->find($brand->id);
            if ($brandExtra === null) {
                $brandExtra = new BrandExtra();
                $brandExtra->id = $brand->id;
            }
            $brandExtra->aliases = is_array($inputData->aliases) && count($inputData->aliases) ?
                json_encode($inputData->aliases) : null;
            if ($brandExtra->hasChanged() && ! $this->brandExtraModel->save($brandExtra)) {
                return $this->failValidationErrors($this->brandExtraModel->errors());
            }
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
    public function saveById(int|string $id)
    {
        $inputData = $this->request->getJSON();
        if (empty($inputData)) {
            return $this->failValidationErrors(lang('RESTful.invalidInput', ['']));
        }

        $brand = $this->brandModel->find($id);
        if ($brand === null) {
            return $this->respond(null, $this->codes['resource_not_found']);
        }

        // Transaction start
        $this->brandModel->db->transStart();

        // Save Brand.
        if (property_exists($inputData, 'picture')) {
            $pictureId = $inputData->picture->id ?? null;
            if ($this->pictureModel->find($pictureId) === null) {
                return $this->respond(null, $this->codes['resource_not_found']);
            }
            $brand->picture_id = $pictureId;
        }
        if ($brand->hasChanged() && ! $this->brandModel->save($brand)) {
            return $this->failValidationErrors($this->brandModel->errors());
        }

        // Save Brand description.
        if (property_exists($inputData, 'description')) {
            $brandExtra = $this->brandExtraModel->find($brand->id);
            $brandExtra->description = $inputData->description;
            if ($brandExtra->hasChanged() && ! $this->brandExtraModel->save($brandExtra)) {
                return $this->failValidationErrors($this->brandExtraModel->errors());
            }
        }

        // Transaction commit
        $this->brandModel->db->transCommit();

        return $this->respondUpdated(['result' => true]);
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
        $fields = ['productType', 'pictures'];
        if ($inputData->filter ?? false) {
            if ($inputData->filter->published ?? false) {
                $filter['published'] = true;
            }
            if ($inputData->filter->type_id ?? false) {
                $filter['productType'] = $inputData->filter->type_id;
            }
            if ($inputData->filter->empty_description ?? false) {
                $filter['emptyDescription'] = true;
                $fields[] = 'description';
            }
            if ($inputData->filter->search_query ?? false) {
                $filter['searchQuery'] = $inputData->filter->search_query;
            }
        }

        $brands = $this->brandModel->query(
            $filter,
            $sort,
            [$limitFrom => $limitStep],
            ['hints' => ['calcRows' => true]],
            $fields
        )->findItems();

        $result = [
            'data' => $brands,
            'pager' => [
                'current' => $page,
                'total'   => $this->brandModel->foundRows(), // ceil($this->brandModel->foundRows() / $limitStep),
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
        $product = $this->brandModel->query(filter:['id' => $id], fields:['description', 'productType', 'pictures'])->first();
        if ($product === null)
            return $this->respond(null, $this->codes['resource_not_found']);
        else
            return $this->respondUpdated($product->toArray());
    }

}
