<?php

namespace App\Controllers\Api\v1;

use App\Controllers\ApiController;
use App\Entities\ProductStorage;
use App\Models\ProductModel;
use App\Models\ProductStorageModel;
use App\Models\StorageModel;

class ProductStorageController extends ApiController
{
    protected StorageModel $storageModel;
    protected ProductModel $productModel;
    protected ProductStorageModel $productStorageModel;

    public function __construct()
    {
        $this->storageModel = model(StorageModel::class);
        $this->productModel = model(ProductModel::class);
        $this->productStorageModel = model(ProductStorageModel::class);
    }

    /**
     * Create or update a batch of Product storages.
     *
     * @return mixed
     */
    public function saveBatch()
    {
        $inputData = $this->request->getJSON();

        if (empty($inputData) || ! is_array($inputData) || count($inputData) === 0) {
            return $this->failValidationErrors(lang('RESTful.invalidInput', ['']));
        }

        // Transaction start
        $this->productModel->db->transStart();

        foreach ($inputData as $item)
        {
            $product = $this->productModel->findByGuid($item->product_guid);
            if ($product === null) {
                $this->addMultiStatusResponse(
                    $item->product_guid,
                    $this->codes['invalid_data'],
                    lang('RESTful.fieldNotFound', ["Product [{$item->product_guid}]"])
                );
                continue;
            }

            $errorMessages = [];
            foreach ($item->storages as $productStorages)
            {
                $storage = $this->storageModel->findByGuid($productStorages->storage_guid);
                if ($storage === null) {
                    $errorMessages[] = lang('RESTful.fieldNotFound', ["Storage [{$productStorages->storage_guid}]"]);
                    continue;
                }

                $productStorage = $this->productStorageModel
                    ->where('product_id', $product->id)
                    ->where('storage_id', $storage->id)
                    ->first();
                if ($productStorage === null) {
                    $productStorage = new ProductStorage();
                }

                $productStorage->product_id  = $product->id;
                $productStorage->storage_id  = $storage->id;

                if (property_exists($productStorages, 'qty')) {
                    $productStorage->qty = $productStorages->qty;
                }

                if ($productStorage->hasChanged() && !$this->productStorageModel->save($productStorage)) {
                    $errorMessages[] = $this->productStorageModel->errors();
                }
            }

            $this->addMultiStatusResponse(
                $item->product_guid,
                count($errorMessages) ? $this->codes['invalid_data'] : $this->codes['updated'],
                count($errorMessages) ? implode('; ', $errorMessages) : null,
            );
        }

        // Transaction commit
        $this->productModel->db->transCommit();

        return $this->respondMultiStatus();
    }

}
