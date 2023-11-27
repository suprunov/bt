<?php

namespace App\Controllers\Api\v1;

use App\Controllers\ApiController;
use App\Entities\ProductLocation;
use App\Models\ProductLocationModel;
use App\Models\ProductModel;

class ProductLocationController extends ApiController
{
    protected ProductLocationModel $productLocationModel;
    protected ProductModel $productModel;

    public function __construct()
    {
        $this->productLocationModel  = model(ProductLocationModel::class);
        $this->productModel  = model(ProductModel::class);
    }

    /**
     * Create a new or update an existing Product Location.
     *
     * @param string $product_guid
     *
     * @return mixed
     */
    public function save(string $product_guid)
    {
        $inputData = $this->request->getJSON();

        if (empty($inputData) && ! is_array($inputData)) {
            return $this->failValidationErrors(lang('RESTful.invalidInput', ['']));
        }

        // Get the Product
        $product = $this->productModel->findByGuid($product_guid);
        if (! $product) {
            return $this->failValidationErrors(lang('RESTful.fieldNotFound', ['product [' . $product_guid . ']']));
        }

        helper('common');

        // Transaction start
        $this->productLocationModel->db->transStart();

        $productLocationsDb = $this->productLocationModel->where('product_id', $product->id)->findAll();
        $productLocationsDb = \App\Helpers\ArrayHelper::array_make_keys($productLocationsDb, 'location_id');

        // Save Product locations
        foreach ($inputData as $item)
        {
            $productLocation = $productLocationsDb[$item->location_wsid] ?? null;
            if ($productLocation === null) {
                $productLocation = new ProductLocation();
            }

            $productLocation->product_id = $product->id;
            $productLocation->location_id = $item->location_wsid;
            if (property_exists($item, 'available')) {
                $productLocation->available = $item->available;
            }
            if (property_exists($item, 'visible')) {
                $productLocation->visible = $item->visible;
            }
            if (property_exists($item, 'feed_available')) {
                $productLocation->feed_available = $item->feed_available;
            }

            if ($productLocation->hasChanged() && ! $this->productLocationModel->save($productLocation)) {
                return $this->failValidationErrors($this->productLocationModel->errors());
            }
        }

        // Transaction commit
        $this->productLocationModel->db->transCommit();

        return $this->respondUpdated();
    }

    /**
     * Create or update a batch of Product locations.
     *
     * @return mixed
     */
    public function saveBatch()
    {
        $inputData = $this->request->getJSON();

        if (empty($inputData) || ! is_array($inputData) || count($inputData) === 0) {
            return $this->failValidationErrors(lang('RESTful.invalidInput', ['']));
        }

        helper('common');

        // Transaction start
        $this->productModel->db->transStart();

        foreach ($inputData as $item)
        {
            // Get the Product
            $product = $this->productModel->findByGuid($item->product_guid);
            if ($product === null) {
                $this->addMultiStatusResponse(
                    $item->product_guid,
                    $this->codes['invalid_data'],
                    lang('RESTful.fieldNotFound', ["Product [{$item->product_guid}]"])
                );
                continue;
            }

            $productLocationsDb = $this->productLocationModel->where('product_id', $product->id)->findAll();
            $productLocationsDb = \App\Helpers\ArrayHelper::array_make_keys($productLocationsDb, 'location_id');

            // Save Product locations
            $errorMessages = [];
            foreach ($item->locations as $location)
            {
                $productLocation = $productLocationsDb[$location->location_wsid] ?? null;
                if ($productLocation === null) {
                    $productLocation = new ProductLocation();
                }

                $productLocation->product_id = $product->id;
                $productLocation->location_id = $location->location_wsid;
                if (property_exists($location, 'available')) {
                    $productLocation->available = $location->available;
                }
                if (property_exists($location, 'visible')) {
                    $productLocation->visible = $location->visible;
                }
                if (property_exists($location, 'feed_available')) {
                    $productLocation->feed_available = $location->feed_available;
                }

                if ($productLocation->hasChanged() && ! $this->productLocationModel->save($productLocation)) {
                    $errorMessages[] = $this->productLocationModel->errors();
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

        return $this->respondUpdated();
    }
}
