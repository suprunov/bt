<?php

namespace App\Controllers\Api\v1;

use App\Controllers\ApiController;
use App\Entities\FeatureValue;
use App\Entities\Model;
use App\Entities\ModelPicture;
use App\Models\CategoryModel;
use App\Models\FeatureModel;
use App\Models\FeatureValueModel;
use App\Models\ModelFeatureValueModel;
use App\Models\ModelModel;
use App\Models\ModelPictureModel;
use App\Models\PictureModel;
use App\Models\ProductFeatureValueModel;
use App\Models\ProductModel;
use App\Models\ProductPictureModel;
use App\Models\ProductTypeModel;

class ModelController extends ApiController
{
    protected ModelModel $modelModel;
    protected FeatureModel $featureModel;
    protected FeatureValueModel $featureValueModel;
    protected ModelFeatureValueModel $modelFeatureValueModel;
    protected ProductFeatureValueModel $productFeatureValueModel;
    protected CategoryModel $categoryModel;
    protected ProductModel $productModel;
    protected ProductTypeModel $productTypeModel;
    protected ModelPictureModel $modelPictureModel;
    protected PictureModel $pictureModel;
    protected ProductPictureModel $productPictureModel;

    public function __construct()
    {
        $this->modelModel  = model(ModelModel::class);
        $this->modelPictureModel  = model(ModelPictureModel::class);
        $this->pictureModel  = model(PictureModel::class);
        $this->productPictureModel  = model(ProductPictureModel::class);

        $this->featureModel  = model(FeatureModel::class);
        $this->featureValueModel = model(FeatureValueModel::class);
        $this->modelFeatureValueModel  = model(ModelFeatureValueModel::class);
        $this->productFeatureValueModel  = model(ProductFeatureValueModel::class);

        $this->categoryModel = model(CategoryModel::class);
        $this->productModel  = model(ProductModel::class);
        $this->productTypeModel  = model(ProductTypeModel::class);
    }

    /**
     * Create a new or update an existing Model.
     *
     * @param string $brand_guid
     * @param string $guid
     *
     * @return mixed
     */
    public function save(string $brand_guid, string $guid)
    {
        $inputData = $this->request->getJSON();

        if (empty($inputData)) {
            return $this->failValidationErrors(lang('RESTful.invalidInput', ['']));
        }

        // Check Product type
        $productType = null;
        if (property_exists($inputData, 'category_guid')) {
            $productType = $this->productTypeModel->findByGuid($inputData->category_guid);
        }
        if ($productType === null) {
            return $this->failValidationErrors(lang('RESTful.fieldNotFound', ['Category [' . $inputData->category_guid .']']));
        }

        // TODO TEMP Check if product exists in old DB
        $sql = "SELECT COALESCE(c5.code, c4.code, c3.code, c2.code, c1.code) code
                FROM products_types c1
                LEFT JOIN products_types c2 ON c2.id = c1.parent_id 
                LEFT JOIN products_types c3 ON c3.id = c2.parent_id 
                LEFT JOIN products_types c4 ON c4.id = c3.parent_id 
                LEFT JOIN products_types c5 ON c5.id = c4.parent_id 
                WHERE c1.id = " . $productType->id;
        $res = db_connect()->query($sql)->getRow();
        $modelOld = match ($res->code) {
            'tyres'     => model(\App\Models\OldTyreModelModel::class)->asObject()->where('id_1c', $guid)->first(),
            'disks'     => model(\App\Models\OldDiskModelModel::class)->asObject()->where('id_1c', $guid)->first(),
            'mototyres' => model(\App\Models\OldMototyreModelModel::class)->asObject()->where('id_1c', $guid)->first(),
            default     => null,
        };
        if ($modelOld === null && in_array($res->code, ['tyres', 'disks', 'mototyres'])) {
            return $this->failValidationErrors(lang('RESTful.invalidInput', ['Model [' . $guid .'] not found in old structure']));
        }

        // Check and get Brand
        $featureBrand = $this->featureModel->findByReference('brand');
        if ($featureBrand === null) {
            return $this->failValidationErrors(lang('RESTful.fieldNotFound', ['Feature with reference=brand']));
        }
        $brandFeatureValue = $this->featureValueModel->findByFeatureGuid($featureBrand->id, $brand_guid);
        if ($brandFeatureValue === null) {
            return $this->failValidationErrors(lang('RESTful.fieldNotFound', ['brand [' . $brand_guid . ']']));
        }

        // Check the existence of a feature Model
        $featureModel = $this->featureModel->findByReference('model');
        if ($featureModel === null) {
            return $this->failValidationErrors(lang('RESTful.fieldNotFound', ['Feature with reference=model']));
        }

        // Transaction start
        $this->featureValueModel->db->transStart();

        // Check and get Model Features
        $modelFeaturesNew = [];
        if (property_exists($inputData, 'features') && count($inputData->features))
        {
            foreach ($inputData->features as $item)
            {
                if (! (property_exists($item, 'guid') && property_exists($item, 'value')))
                    return $this->failValidationErrors(lang('RESTful.invalidInput', ['Feature values']));

                $feature = $this->featureModel->findByGuid($item->guid); // TODO move above the loop, change to the package getting
                if ($feature === null)
                    return $this->failValidationErrors(lang('RESTful.fieldNotFound', ['feature [' . $item->guid . ']']));

                // Check reference values
                if ($feature->reference !== 'none') {
                    $value = $this->featureValueModel->findByFeatureGuid($feature->id, $item->value);
                    if ($value === null)
                        return $this->failValidationErrors(lang('RESTful.fieldNotFound', ['feature value [' . $item->value . ']']));
                } else {
                    $value = $this->featureValueModel->findByFeatureValue($feature->id, $item->value);
                    if ($value === null) { // TODO the same block as in the product
                        // Save Feature value
                        $value = new FeatureValue();
                        $value->reference = $feature->reference;
                        $value->feature_id = $feature->id;
                        $value->value = $item->value;
                        $value->setCode($value->value);
                        $value->value_double = null;
                        if (in_array($feature->type, ['double', 'boolean'])) {
                            $value_double = str_replace(',','.', $value->value);
                            $value->value_double = ! preg_match('~[^0-9\.-]~', $value_double) && is_numeric($value_double) ?
                                (float)$value_double : null;
                            if ($value->value_double === null) {
                                return $this->failValidationErrors(lang('RESTful.invalidInput', ['feature [' . $feature->guid . ']: invalid type of value. Double type is required']));
                            }
                        }

                        if ($value->hasChanged() && ! $this->featureValueModel->save($value)) {
                            return $this->failValidationErrors($this->featureValueModel->errors());
                        }
                        $value->id = $this->featureValueModel->getInsertID();
                    }
                }

                $modelFeaturesNew[] = [
                    'feature_id' => $feature->id,
                    'value_id' => $value->id,
                ];
            }
        }

        // Save Feature value of this Model
        $featureValue = $this->featureValueModel->findByFeatureGuid($featureModel->id, $guid);
        $modelFeaturesOld = [];
        if ($featureValue === null) {
            $crud = 'create';
            $featureValue = new FeatureValue();
        } else {
            $crud = 'update';
            $modelFeaturesOld = $this->modelFeatureValueModel->where('model_id', $featureValue->id)->findAll();
        }
        $featureValue->reference = 'model';
        $featureValue->feature_id = $featureModel->id;
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
        $featureValue->id = $this->featureValueModel->getInsertID() ?: $featureValue->id;

        // Save Model
        $model = $this->modelModel->find($featureValue->id);
        if ($model === null) {
            $model     = new Model();
            $model->id = $featureValue->id;
        }
        $model->name              = $featureValue->value;
        $model->brand_id          = $brandFeatureValue->id;
        $model->obsolete_model_id = $modelOld->id ?? null; // TEMP
        if (property_exists($inputData, 'aliases')) {
            $model->aliases = is_array($inputData->aliases) && count($inputData->aliases) ?
                json_encode($inputData->aliases) : null;
        }
        if (property_exists($inputData, 'novelty')) {
            $model->novelty = $inputData->novelty;
            // Update the Novelty field for all products of this model.
            if ($model->hasChanged('novelty')) {
                $modelProducts = $this->productFeatureValueModel
                    ->where('feature_id', $featureModel->id)
                    ->where('value_id', $model->id)
                    ->findAll();
                foreach ($modelProducts as $modelProduct) {
                    $this->productModel->update($modelProduct->product_id, ['novelty' => $model->novelty]); // TODO batch update
                }
            }
        }

        if ($model->hasChanged() && ! $this->modelModel->save($model)) {
            return $this->failValidationErrors($this->modelModel->errors());
        }

        // Save Model Features
        $newFeatures = array_column($modelFeaturesNew, 'value_id', 'feature_id');
        $oldFeatures = array_column($modelFeaturesOld, 'value_id', 'feature_id');
        $featuresToAdd = $newFeatures;
        $featuresToRemove = $oldFeatures;
        foreach ($newFeatures as $k => $item) {
            if (isset($oldFeatures[$k])) {
                if ($oldFeatures[$k] === $item) {
                    unset($featuresToAdd[$k], $featuresToRemove[$k]);
                }
            }
        }
        if (count($featuresToRemove)) {
            $this->modelFeatureValueModel
                ->where('model_id', $model->id)
                ->whereIn('feature_id', array_keys($featuresToRemove))
                ->delete();
        }
        $featuresToAddArr = [];
        foreach ($featuresToAdd as $k => $item) {
            $featuresToAddArr[] = ['model_id' => $model->id, 'feature_id' => $k, 'value_id' => $item];
        }
        if (count($featuresToAddArr)) {
            if ($this->modelFeatureValueModel->insertBatch($featuresToAddArr) === false) {
                return $this->failValidationErrors($this->modelFeatureValueModel->errors());
            }
        }

        // Update product features
        if (count($featuresToRemove)) {
            $this->productFeatureValueModel
                ->where('model_id', $model->id)
                ->where('feature_id', array_keys($featuresToRemove))
                ->delete();
        }
        if (count($featuresToAddArr)) {
            $modelProducts = $this->productFeatureValueModel
                ->where('feature_id', $featureModel->id)
                ->where('value_id', $model->id)
                ->findAll();
            $featuresValuesToAddArr = [];
            foreach ($modelProducts as $modelProduct) {
                foreach ($featuresToAddArr as $featureToAdd) {
                    $featuresValuesToAddArr[] = [
                        'type_id' => $modelProduct->type_id,
                        'product_id' => $modelProduct->product_id,
                        'feature_id' => $featureToAdd['feature_id'],
                        'value_id' => $featureToAdd['value_id'],
                        'model_id' => $featureToAdd['model_id'],
                    ];
                }
            } 
            if (count($featuresValuesToAddArr)) {
                if ($this->productFeatureValueModel->insertBatch($featuresValuesToAddArr) === false) {
                    return $this->failValidationErrors($this->productFeatureValueModel->errors());
                }
            }
        }

        // Transaction commit
        $this->featureValueModel->db->transCommit();

        return $crud == 'create' ? $this->respondCreated() : $this->respondUpdated();
    }

    /**
     *  Update an existing Model.
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

        $model = $this->modelModel->find($id);
        if ($model === null) {
            return $this->respond(null, $this->codes['resource_not_found']);
        }

        // Transaction start
        $this->modelModel->db->transStart();

        // Save Model.
        if (property_exists($inputData, 'description')) {
            $model->description = $inputData->description;
        }
        if ($model->hasChanged() && ! $this->modelModel->save($model)) {
            return $this->failValidationErrors($this->modelModel->errors());
        }

        helper('common');

        // Save Model pictures.
        if (property_exists($inputData, 'pictures')) {
            $modelPicturesDb = $this->modelPictureModel->query(['model' => $model->id], limit: [])->findItems();
            $modelPicturesDb = \App\Helpers\ArrayHelper::array_make_keys($modelPicturesDb, 'picture_id');
            foreach ($inputData->pictures as $colorData) {
                foreach ($colorData->items as $pictureData) {
                    if ($this->pictureModel->find($pictureData->id) === null) {
                        return $this->respond(null, $this->codes['resource_not_found']);
                    }
                    $modelPicture = $this->modelPictureModel->query(
                        ['model' => $model->id, 'picture' => $pictureData->id]
                    )->first();
                    if ($modelPicture === null) {
                        $modelPicture = new ModelPicture();
                        $modelPicture->model_id = $model->id;
                        $modelPicture->picture_id = $pictureData->id;
                    }
                    $modelPicture->color_id = $colorData->color->id;
                    $modelPicture->sort = $pictureData->sort;
                    if ($modelPicture->hasChanged() && ! $this->modelPictureModel->save($modelPicture)) {
                        return $this->failValidationErrors($this->modelPictureModel->errors());
                    }
                    unset($modelPicturesDb[$pictureData->id]);
                }
            }
            // Delete old pictures
            foreach ($modelPicturesDb as $modelPicture) {
                if (! $this->modelPictureModel->delete($modelPicture->id)) {
                    return $this->failValidationErrors($this->modelPictureModel->errors());
                }
            }
        }

        // Save Product description
        if (property_exists($inputData, 'description')) {
            $this->productModel->builder()
                ->where('model_id', $model->id)
                ->update(['description' => $model->description]);
        }
        // Save Product pictures
        /*if (property_exists($inputData, 'pictures')) {
            // remove old pictures
            $modelId = $model->id;
            $this->productPictureModel
                ->whereIn('product_id', function ($query) use ($modelId) {
                    $query->select('p.id')
                        ->from(tbl('products') . ' p')
                        ->where('p.model_id', $modelId);
                })
                ->delete();
            // add new pictires
            $modelProducts = $this->productModel->query(['model' => $model->id], limit: [])->findItems();
            foreach ($modelProducts as $modelProduct) {

            }
        }*/

        // Transaction commit
        $this->modelModel->db->transCommit();

        return $this->respondUpdated(['result' => true]);
    }

    /**
     * Search for Models by filter.
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
        $fields = ['productType', 'brand', 'pictures'];
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

        $models = $this->modelModel->query(
            $filter,
            $sort,
            [$limitFrom => $limitStep],
            ['hints' => ['calcRows' => true]],
            $fields
        )->findItems();

        $result = [
            'data' => $models,
            'pager' => [
                'current' => $page,
                'total'   => $this->modelModel->foundRows(), //ceil($this->modelModel->foundRows() / $limitStep),
                'per_page'=> $limitStep,
            ]
        ];
        return $this->respondUpdated($result);
    }

    /**
     * Get Model by ID.
     *
     * @param int|string $id
     *
     * @return mixed
     */
    public function getById(int|string $id)
    {
        $model = $this->modelModel->query(filter:['id' => $id], fields:['description', 'productType', 'pictures', 'brand', 'reviewTotals'])->first();
        if ($model === null)
            return $this->respond(null, $this->codes['resource_not_found']);
        else
            return $this->respondUpdated($model->toArray());
    }
}
