<?php

namespace App\Controllers\Api\v1;

use App\Controllers\ApiController;
use App\Models\ProductTypeModel;
use App\Entities\ProductType;
use App\Models\CategoryModel;
use App\Entities\Category;

class ProductTypeController extends ApiController
{
    protected ProductTypeModel $productTypeModel;
    protected CategoryModel $categoryModel;

    public function __construct()
    {
        $this->productTypeModel  = model(ProductTypeModel::class);
        $this->categoryModel = model(CategoryModel::class);
    }

    /**
     * Create a new or update an existing Product type.
     *
     * @param string guid
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
        $this->productTypeModel->db->transStart();

        // Save Product type
        $productType = $this->productTypeModel->findByGuid($guid);
        if ($productType === null) {
            $crud = 'create';
            $productType = new ProductType();
        } else {
            $crud = 'update';
        }
        $productType->guid = $guid;
        if (property_exists($inputData, 'parent_guid')) {
            if ($inputData->parent_guid) {
                $productTypeParent = $this->productTypeModel->findByGuid($inputData->parent_guid);
                if ($productTypeParent === null)
                    return $this->failValidationErrors(lang('RESTful.fieldNotFound', ['Parent']));
            }
            $productType->parent_id = $productTypeParent->id ?? null;
            $productType->obsolete_type_id = $productTypeParent->obsolete_type_id ?? null; // TEMP
        }
        if (property_exists($inputData, 'name')) {
            $productType->name = trim($inputData->name);
            if ($crud == 'create') {
                $productType->setCode($productType->name);
                $productTypeWithTheSameCode = $this->productTypeModel->findByCode($productType->code); // TODO remove this part.. just write as it is and then use extra persistence field - url which consists of code+id
                if ($productTypeWithTheSameCode != null) {
                    $productType->setCode($productType->name . '_' . time());
                }
            }
        }
        if (property_exists($inputData, 'published')) {
            $productType->published = $inputData->published;
        }

        if ($productType->hasChanged() && ! $this->productTypeModel->save($productType)) {
            return $this->failValidationErrors($this->productTypeModel->errors());
        }

        // Save Category - now it's the same as the Product type. Later it'll be a separate entity.
        $productType->id = $productType->id ?: $this->productTypeModel->getInsertID();
        $category = $this->categoryModel->find($productType->id);
        if ($category === null) {
            $category = new Category();
            $category->id   = $productType->id;
            $category->code = $productType->code;
        }
        $category->name      = $productType->name;
        $category->published = $productType->published;
        $category->parent_id = $productType->parent_id;

        if ($category->hasChanged() && ! $this->categoryModel->save($category)) {
            return $this->failValidationErrors($this->categoryModel->errors());
        }

        // Transaction commit
        $this->productTypeModel->db->transCommit();

        return $crud == 'create' ? $this->respondCreated() : $this->respondUpdated();
    }

    /**
     * Search for Product types by filter.
     *
     * @return mixed
     */
    public function get()
    {
        $productTypes = $this->productTypeModel->query(
            ['published' => true],
            ['sort' => 'asc', 'name' => 'asc'],
            []
        )->findItems();

        return $this->respondUpdated($this->productTypeModel->buildTree($productTypes));
    }

}
