<?php

namespace App\Controllers\Api\v1;

use App\Controllers\ApiController;
use App\Entities\ProductPrice;
use App\Models\PriceModel;
use App\Models\ProductModel;
use App\Models\ProductPriceModel;
use CodeIgniter\HTTP\Response;

class ProductPriceController extends ApiController
{
    protected PriceModel $priceModel;
    protected ProductModel $productModel;
    protected ProductPriceModel $productPriceModel;

    public function __construct()
    {
        $this->priceModel        = model(PriceModel::class);
        $this->productModel      = model(ProductModel::class);
        $this->productPriceModel = model(ProductPriceModel::class);
    }

    /**
     * Create a new or update an existing Product price.
     *
     * @param string $product_guid
     * @param string $guid
     *
     * @return Response
     */
    public function save(string $product_guid, string $guid): Response
    {
        $inputData = $this->request->getJSON();

        if (empty($inputData)) {
            return $this->failValidationErrors(lang('RESTful.invalidInput', ['']));
        }

        $product = $this->productModel->findByGuid($product_guid);
        if ($product === null)
            return $this->failValidationErrors(lang('RESTful.fieldNotFound', ['Product']));
        $price = $this->priceModel->findByGuid($guid);
        if ($price === null)
            return $this->failValidationErrors(lang('RESTful.fieldNotFound', ['Price type']));

        $productPrice = $this->productPriceModel
            ->where('product_id', $product->id)
            ->where('price_id', $price->id)
            ->first();
        if ($productPrice === null) {
            $crud = 'create';
            $productPrice = new ProductPrice();
        } else {
            $crud = 'update';
        }

        $productPrice->product_id = $product->id;
        $productPrice->price_id   = $price->id;
        if (property_exists($inputData, 'value')) {
            $productPrice->value = $inputData->value;
        }
        if (property_exists($inputData, 'rrc')) {
            $productPrice->rrc = $inputData->rrc;
        }
        if (property_exists($inputData, 'bonus_points')) {
            $productPrice->bonus_points = $inputData->bonus_points;
        }

        if ($productPrice->hasChanged() && ! $this->productPriceModel->save($productPrice)) {
            return $this->failValidationErrors($this->productPriceModel->errors());
        } else {
            return $crud == 'create' ? $this->respondCreated() : $this->respondUpdated();
        }
    }

    /**
     * Create or update a batch of Product prices.
     *
     * @return Response
     */
    public function saveBatch(): Response
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
            foreach ($item->prices as $productPrices)
            {
                $price = $this->priceModel->findByGuid($productPrices->price_guid);
                if ($price === null) {
                    $errorMessages[] = lang('RESTful.fieldNotFound', ["Price type [{$productPrices->price_guid}]"]);
                    continue;
                }

                $productPrice = $this->productPriceModel
                    ->where('product_id', $product->id)
                    ->where('price_id', $price->id)
                    ->first();
                if ($productPrice === null) {
                    $productPrice = new ProductPrice();
                }

                $productPrice->product_id = $product->id;
                $productPrice->price_id   = $price->id;
                if (property_exists($productPrices, 'value')) {
                    $productPrice->value = $productPrices->value;
                }
                if (property_exists($productPrices, 'rrc')) {
                    $productPrice->rrc = $productPrices->rrc;
                }
                if (property_exists($productPrices, 'bonus_points')) {
                    $productPrice->bonus_points = $productPrices->bonus_points;
                }

                if ($productPrice->hasChanged() && ! $this->productPriceModel->save($productPrice)) {
                    $errorMessages[] = $this->productPriceModel->errors();
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
