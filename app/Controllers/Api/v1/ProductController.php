<?php

namespace App\Controllers\Api\v1;

use App\Controllers\ApiController;
use App\Entities\FeatureValue;
use App\Entities\ProductPicture;
use App\Models\BrandModel;
use App\Models\CategoryModel;
use App\Models\ColorModel;
use App\Models\FeatureModel;
use App\Models\FeatureValueModel;
use App\Models\ModelFeatureValueModel;
use App\Models\ModelModel;
use App\Models\PictureModel;
use App\Models\ProductFeatureValueModel;
use App\Models\FeatureFilterModel;
use App\Models\ProductModel;
use App\Entities\Product;
use App\Models\ProductPictureModel;
use App\Models\ProductTypeModel;

class ProductController extends ApiController
{
    protected ProductModel $productModel;
    protected CategoryModel $categoryModel;
    protected FeatureModel $featureModel;
    protected FeatureValueModel $featureValueModel;
    protected FeatureFilterModel $featureFilterModel;
    protected ProductFeatureValueModel $productFeatureValueModel;
    protected ProductTypeModel $productTypeModel;
    protected ProductPictureModel $productPictureModel;
    protected ModelFeatureValueModel $modelFeatureValueModel;
    protected ColorModel $colorModel;
    protected BrandModel $brandModel;
    protected ModelModel $modelModel;

    protected PictureModel $pictureModel;

    public function __construct()
    {
        $this->productModel  = model(ProductModel::class);
        $this->categoryModel = model(CategoryModel::class);
        $this->featureModel  = model(FeatureModel::class);
        $this->featureValueModel = model(FeatureValueModel::class);
        $this->productFeatureValueModel  = model(ProductFeatureValueModel::class);
        $this->productTypeModel  = model(ProductTypeModel::class);
        $this->productPictureModel  = model(ProductPictureModel::class);
        $this->featureFilterModel  = model(FeatureFilterModel::class);
        $this->modelFeatureValueModel  = model(ModelFeatureValueModel::class);
        $this->colorModel   = model(ColorModel::class);
        $this->brandModel   = model(BrandModel::class);
        $this->modelModel   = model(ModelModel::class);
        $this->pictureModel = model(PictureModel::class);
    }

    /**
     * Create a new or update an existing Product.
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

        helper('common');

        // Check Product type
        $productType = null;
        if (property_exists($inputData, 'category_guid')) {
            $productType = $this->productTypeModel->findByGuid($inputData->category_guid);
        }
        if ($productType === null) {
            return $this->failValidationErrors(lang('RESTful.fieldNotFound', ['Category']));
        }

        // TODO TEMP Check if product exists in old DB
        $sql = "SELECT COALESCE(c5.code, c4.code, c3.code, c2.code, c1.code) code
                FROM products_types c1
                LEFT JOIN products_types c2 ON c2.id = c1.parent_id 
                LEFT JOIN products_types c3 ON c3.id = c2.parent_id 
                LEFT JOIN products_types c4 ON c4.id = c3.parent_id 
                LEFT JOIN products_types c5 ON c5.id = c4.parent_id 
                WHERE c1.id = " . $productType->id;
        $productRootType = db_connect()->query($sql)->getRow();
        $productOld = match ($productRootType->code) {
            'tyres'       => model(\App\Models\OldTyreModel::class)->asObject()->where('id_1c', $guid)->first(),
            'disks'       => model(\App\Models\OldDiskModel::class)->asObject()->where('id_1c', $guid)->first(),
            'mototyres'   => model(\App\Models\OldMototyreModel::class)->asObject()->where('id_1c', $guid)->first(),
            'services'    => model(\App\Models\OldServiceModel::class)->asObject()->where('id_1c', $guid)->first(),
            'maintenance' => model(\App\Models\OldServiceModel::class)->asObject()->where('id_1c', $guid)->first(),
            default       => model(\App\Models\OldAccessoryModel::class)->asObject()->where('id_1c', $guid)->first(),
        };
        if ($productOld === null /*&& $inputData->published == true*/) {
            return $this->failValidationErrors(lang('RESTful.invalidInput', ['Product [' . $guid .'] not found in old structure']));
        }

        // Transaction start
        $this->productModel->db->transStart();

        // Check and get features
        $productFeatures = [];
        $modelFeaturesDb = [];
        $model = null;
        if (property_exists($inputData, 'features') && count($inputData->features))
        {
            foreach ($inputData->features as $item)
            {
                if (! (property_exists($item, 'guid') && property_exists($item, 'value'))) {
                    return $this->failValidationErrors(lang('RESTful.invalidInput', ['Feature values']));
                }

                $feature = $this->featureModel->findByGuid($item->guid); // TODO move above the loop, change to the package getting
                if ($feature === null) {
                    return $this->failValidationErrors(lang('RESTful.fieldNotFound', ['feature [' . $item->guid . ']']));
                }

                // Check reference values
                if ($feature->reference !== 'none') {
                    if ($item->value !== null) {
                        $value = $this->featureValueModel->findByFeatureGuid($feature->id, $item->value);
                        if ($value === null)
                            return $this->failValidationErrors(lang('RESTful.fieldNotFound', ['feature value [' . $item->value . ']']));
                    } else {
                        $value = new \stdClass();
                        $value->id = null;
                        $value->code = null;
                        $value->value = null;
                        $value->value_alias = null;
                        $value->description = null;
                    }
                } else {
                    if ($item->value !== null)
                    {
                        $value = $this->featureValueModel->findByFeatureValue($feature->id, $item->value);
                        if ($value === null) { // TODO the same block as in the model
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
                    } else {
                        $value = new \stdClass();
                        $value->id = null;
                        $value->code = null;
                        $value->value = null;
                        $value->value_alias = null;
                        $value->description = null;
                    }
                }

                // Final set of features for update
                $productFeatures[] = [
                    'feature_id'        => $feature->id,
                    'value_id'          => $value->id,
                    'model_id'          => null,
                    // needed to generate the product name:
                    'code'              => $feature->code,
                    'value'             => $value->value,
                    'value_alias'       => $value->value_alias,
                ];

                if ($feature->reference === 'model') {
                    // Magic trick because 1S sucks: fill product_type_id field for this model if it's not filled yet.
                    $model = $this->modelModel->find($value->id);
                    if (! $model->product_type_id) {
                        $this->modelModel->update($value->id, ['product_type_id' => $productType->id]);
                    }
                    $modelFeaturesDb = $this->modelFeatureValueModel->query(
                        filter:['model' => $model->id],
                        limit: [],
                        fields:['feature', 'value']
                    )->findItems();
                    foreach ($modelFeaturesDb as $modelFeature) {
                        $productFeatures[] = [
                            'feature_id'        => $modelFeature->feature_id,
                            'value_id'          => $modelFeature->value_id,
                            'model_id'          => $modelFeature->model_id,
                            // needed to generate the product name:
                            'code'              => $modelFeature->feature->code,
                            'value'             => $modelFeature->value->value,
                            'value_alias'       => $modelFeature->value->value_alias,
                        ];
                    }
                }
                // Create extra (virtual) features:
                // 1. Country
                elseif ($feature->reference === 'brand') {
                    $brand = $this->brandModel->find($value->id);
                    $countryId = $brand->country_id;
                    if ($countryId !== null) {
                        $featureCountry = $this->featureModel->findByReference('country');
                        if ($featureCountry === null) {
                            return $this->failValidationErrors(lang('RESTful.fieldNotFound', ['The feature with reference_type=country']));
                        }
                        $featureCountryValue = $this->featureValueModel->find($countryId);
                        $productFeatures[] = [
                            'feature_id'        => $featureCountry->id,
                            'value_id'          => $countryId,
                            'model_id'          => null,
                            // needed to generate the product name:
                            'code'              => $featureCountry->code,
                            'value'             => $featureCountryValue->value,
                            'value_alias'       => $featureCountryValue->value_alias,
                        ];
                    }
                }
                // 2. Color group
                elseif ($feature->reference === 'color') {
                    $color = $this->colorModel->find($value->id);
                    $colorGroupId = $color->group_id;
                    if ($colorGroupId !== null) {
                        $featureColorGroup = $this->featureModel->findByReference('color_group');
                        if ($featureColorGroup === null) {
                            return $this->failValidationErrors(lang('RESTful.fieldNotFound', ['The feature with reference_type=color_group']));
                        }
                        $featureColorGroupValue = $this->featureValueModel->find($colorGroupId);
                        $productFeatures[] = [
                            'feature_id'        => $featureColorGroup->id,
                            'value_id'          => $colorGroupId,
                            'model_id'          => null,
                            // needed to generate the product name:
                            'code'              => $featureColorGroup->code,
                            'value'             => $featureColorGroupValue->value,
                            'value_alias'       => $featureColorGroupValue->value_alias,
                        ];
                    }
                }
            }
        }

        // Save the Product
        $product = $this->productModel->findByGuid($guid);
        $productFeaturesDb = [];
        if ($product === null) {
            $crud = 'create';
            $product = new Product;
        } else {
            $crud = 'update';
            $productFeaturesDb = $this->productFeatureValueModel->where('product_id', $product->id)->findAll();
        }

        $product->guid                = $guid;
        $product->obsolete_product_id = $productOld->id ?? null;
        $product->type_id             = $productType->id;
        $product->novelty             = $model->novelty ?? 0; // Copy this field from the model.
        $product->model_id            = $model->id ?? null;

        if (property_exists($inputData, 'code')) {
            $product->product_code = $inputData->code;
        }
        if (property_exists($inputData, 'type')) {
            $product->kind = $inputData->type;
        }
        if (property_exists($inputData, 'vendor_code')) {
            $product->vendor_code = trim($inputData->vendor_code);
        }
        if (property_exists($inputData, 'unit')) {
            $product->unit = trim($inputData->unit);
        }
        if (property_exists($inputData, 'bonus_points_apply')) {
            $product->bonus_points_apply = $inputData->bonus_points_apply;
        }
        if (property_exists($inputData, 'published')) {
            $product->published = $inputData->published;
        }
        if (property_exists($inputData, 'name')) {
            $product->name = $productName ?? trim($inputData->name);
            if ( $crud == 'create') {
                $product->features = json_encode($productFeatures); // Needed only for product code generation.
                $product->type     = json_encode($productType);        // Needed only for product code generation.
                $product->setCode($product->name->full);
            }
        }

        if ($product->hasChanged() && ! $this->productModel->save($product)) {
            return $this->failValidationErrors($this->productModel->errors());
        }
        $product->id = $product->id ?: $this->productModel->getInsertID();


        // Update Product feature values.
        $productFeatures   = array_combine(array_column($productFeatures, 'feature_id'), $productFeatures);
        $productFeaturesDb = array_combine(array_column($productFeaturesDb, 'feature_id'), $productFeaturesDb);
        $featuresToAdd     = $productFeatures;
        $featuresToRemove  = $productFeaturesDb;
        foreach ($productFeatures as $k => $item) {
            if (isset($productFeaturesDb[$k])) {
                if ($productFeaturesDb[$k]->value_id === $item['value_id'] &&
                    $productFeaturesDb[$k]->type_id === $product->type_id &&
                    $productFeaturesDb[$k]->model_id === $item['model_id']
                ) {
                    unset($featuresToAdd[$k], $featuresToRemove[$k]);
                }
            }
        }
        if (count($featuresToRemove)) {
            $this->productFeatureValueModel
                ->where('product_id', $product->id)
                ->whereIn('feature_id', array_keys($featuresToRemove))
                ->delete();
        }
        $featuresToAddFormatted = [];
        foreach ($featuresToAdd as $item) {
            $featuresToAddFormatted[] = [
                'type_id'    => $product->type_id,
                'product_id' => $product->id,
                'feature_id' => $item['feature_id'],
                'value_id'   => $item['value_id'],
                'model_id'   => $item['model_id'] ?? null,
            ];
        }
        if (count($featuresToAddFormatted)) {
            if ($this->productFeatureValueModel->insertBatch($featuresToAddFormatted) === false) {
                return $this->failValidationErrors($this->productFeatureValueModel->errors());
            }
        }


        // Update Feature filters.
        if (count($featuresToAdd)) {
            $featureFilters = $this->featureFilterModel
                ->where('product_type_id', $productType->id)
                ->whereIn('feature_id', array_column($featuresToAdd, 'feature_id'))
                ->asArray()->findAll();
            $featureFilters = \App\Helpers\ArrayHelper::array_make_keys($featureFilters, 'feature_id');
            $featureFiltersToAdd = [];
            foreach ($featuresToAdd as $item) {
                if (!isset($featureFilters[$item['feature_id']])) {
                    $feature = $this->featureModel->find($item['feature_id']);
                    $published       = $feature->published;
                    $cardPublished   = 1;
                    $filterPublished = 1;
                    $cardSimilar     = 0;
                    // HACK because of dirty 1ass
                    if ($productRootType->code == 'accessories') { // TODO feature 'brand' ?
                        $cardSimilar = 1;
                        if (in_array($feature->code, ['model', 'country', 'sale'])) {
                            $published       = 0;
                            $cardPublished   = 0;
                            $filterPublished = 0;
                            $cardSimilar     = 0;
                        }
                        if ($feature->code == 'volume') {
                            $filterPublished = 0;
                            $cardSimilar     = 0;
                        }
                    }
                    $featureFiltersToAdd[] = [
                        'product_type_id'  => $productType->id,
                        'feature_id'       => $feature->id,
                        'name'             => $feature->name,
                        'published'        => $published,
                        'card_published'   => $cardPublished,
                        'filter_published' => $filterPublished,
                        'card_similar'     => $cardSimilar,
                    ];
                }
            }
            if (count($featureFiltersToAdd)) {
                if ($this->featureFilterModel->insertBatch($featureFiltersToAdd) === false) {
                    return $this->failValidationErrors($this->featureFilterModel->errors());
                }
            }
        }
        
        // Transaction commit
        $this->productModel->db->transCommit();

        return $crud == 'create' ? $this->respondCreated() : $this->respondUpdated();
    }

    /**
     *  Update an existing Product.
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

        $product = $this->productModel->find($id);
        if ($product === null) {
            return $this->respond(null, $this->codes['resource_not_found']);
        }

        helper('common');

        // Transaction start
        $this->productModel->db->transStart();

        // Save product.
        if (property_exists($inputData, 'description')) {
            $product->description = $inputData->description;
        }
        if ($product->hasChanged() && ! $this->productModel->save($product)) {
            return $this->failValidationErrors($this->productModel->errors());
        }

        // Save Product pictures.
        if (property_exists($inputData, 'pictures') && is_array($inputData->pictures)) {
            $productPicturesDb = $this->productPictureModel->where('product_id', $product->id)->findAll();
            $productPicturesDb = \App\Helpers\ArrayHelper::array_make_keys($productPicturesDb, 'picture_id');
            foreach ($inputData->pictures as $picture) {
                if ($this->pictureModel->find($picture->id) === null) {
                    return $this->respond(null, $this->codes['resource_not_found']);
                }
                $productPicture = $productPicturesDb[$picture->id] ?? null;
                if ($productPicture === null) {
                    $productPicture = new ProductPicture();
                }
                $productPicture->product_id = $product->id;
                $productPicture->picture_id = (string)$picture->id;
                $productPicture->sort       = isset($picture->sort) ? (string)$picture->sort : null;
                if ($productPicture->hasChanged() && ! $this->productPictureModel->save($productPicture)) {
                    return $this->failValidationErrors($this->productPictureModel->errors());
                }
                unset($productPicturesDb[$picture->id]);
            }
            // Delete old product pictures.
            foreach ($productPicturesDb as $productPictureDb) {
                if (! $this->productPictureModel->delete($productPictureDb->id)) {
                    return $this->failValidationErrors($this->productPictureModel->errors());
                }
            }
        }

        // Transaction commit
        $this->productModel->db->transCommit();

        return $this->respondUpdated(['result' => true]);
    }

    /**
     * Get Product by ID.
     *
     * @param int|string $id
     *
     * @return mixed
     */
    public function getById(int|string $id)
    {
        //$location = 38; // TODO
        $product = $this->productModel->queryExtended(
            filter:['id' => $id/*, 'location' => $location*/], // TODO location ?
            fields:['features', 'description', 'pictures', 'price', 'promotions', 'model', 'reviewTotals']
        )->first();
        if ($product === null)
            return $this->respond(null, $this->codes['resource_not_found']);
        else
            return $this->respondUpdated($product->toArray());
    }

    /**
     * Search for products by filter.
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

        // filter
        $filter = [];
        if ($inputData->filter ?? false) {
            if ($inputData->filter->published ?? false) {
                $filter['published'] = true;
            }
            if ($inputData->filter->available ?? false) {
                $filter['available'] = true;
            }
            if ($inputData->filter->type_id ?? false) {
                $filter['type'] = $inputData->filter->type_id;
            }
            if ($inputData->filter->location_id ?? false) {
                $filter['location'] = $inputData->filter->location_id;
            }
            if ($inputData->filter->empty_description ?? false) {
                $filter['emptyDescription'] = true;
            }
            if ($inputData->filter->search_query ?? false) { // TEMP it has to be a search module
                if (is_numeric($inputData->filter->search_query))
                    $filter['productCode'] = $inputData->filter->search_query;
                else
                    $filter['searchQuery'] = $inputData->filter->search_query;
            }
        }

        $products = $this->productModel->getProducts(
            $filter,
            $sort,
            [$limitFrom => $limitStep],
            [],
            ['features', 'description', 'price', 'pictures']
        );

        // get totals
        $productTotals = $this->productModel->getTotals($filter)->asArray()->first();
        $total  = (int)$productTotals['total'];

        $result = [
            'data' => array_values($products),
            'pager' => [
                'current' => $page,
                'total'   => $total, //ceil($total / $limitStep),
                'per_page'=> $limitStep,
            ]
        ];
        return $this->respondUpdated($result);
    }

}
