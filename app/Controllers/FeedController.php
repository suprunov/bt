<?php

namespace App\Controllers;

use App\Models\BrandModel;
use App\Models\CategoryModel;
use App\Models\ColorModel;
use App\Models\FeatureFilterModel;
use App\Models\FeatureModel;
use App\Models\FeatureValueModel;
use App\Models\ModelFeatureValueModel;
use App\Models\ModelModel;
use App\Models\PictureModel;
use App\Models\ProductFeatureValueModel;
use App\Models\ProductModel;
use App\Models\ProductPictureModel;
use App\Models\ProductTypeModel;

class FeedController extends BaseController
{
    protected ProductModel $productModel;
    //protected CategoryModel $categoryModel;
    //protected FeatureModel $featureModel;
    //protected FeatureValueModel $featureValueModel;
    //protected FeatureFilterModel $featureFilterModel;
    //protected ProductFeatureValueModel $productFeatureValueModel;
    //protected ProductTypeModel $productTypeModel;
    //protected ProductPictureModel $productPictureModel;
    //protected ModelFeatureValueModel $modelFeatureValueModel;
    //protected ColorModel $colorModel;
    //protected BrandModel $brandModel;
    //protected ModelModel $modelModel;
    //protected PictureModel $pictureModel;

    protected string $header;
    protected string $footer;
    protected array $products;

    protected int $locationId;
    protected int $priceId;

    public function __construct()
    {
        $this->productModel = model(ProductModel::class);
        //$this->categoryModel = model(CategoryModel::class);
        //$this->featureModel  = model(FeatureModel::class);
        //$this->featureValueModel = model(FeatureValueModel::class);
        //$this->productFeatureValueModel  = model(ProductFeatureValueModel::class);
        //$this->productTypeModel  = model(ProductTypeModel::class);
        //$this->productPictureModel  = model(ProductPictureModel::class);
        //$this->featureFilterModel  = model(FeatureFilterModel::class);
        //$this->modelFeatureValueModel  = model(ModelFeatureValueModel::class);
        //$this->colorModel   = model(ColorModel::class);
        //$this->brandModel   = model(BrandModel::class);
        //$this->modelModel   = model(ModelModel::class);
        //$this->pictureModel = model(PictureModel::class);

        $this->locationId = 38; // TODO
        $this->priceId = 25; // TODO
    }

    public function create(string $feed, string $location)
    {
        /*$method = 'getProductsFor' . ucfirst($feed);

        if (! method_exists($this, $method)) {
            exit('There is no such a feed');
        }*/

        // TODO
        $pickups = $this->productModel->db->query("
            SELECT p.id_pickup, p.pickup_name name, IF(pickup_type = 1, 'ours', 'partners') type, 
                   IF(s.external_link, s.external_link, s.uri) uri, 1 nofollow
            FROM (blacktyres.bt_pickup p)
            LEFT JOIN blacktyres.structure s ON s.name = p.pickup_name
            WHERE `p`.`location` = {$this->locationId}
            AND `p`.`pickup_status` = 1
            ORDER BY p.pickup_type DESC, p.sort, p.pickup_name
        ")->getResult();

        $feedPath = FCPATH . 'xml/feeds/avito.xml';

        $fp = fopen($feedPath, 'w');

        $viewOptions = ['debug' => false];

        if (flock($fp, LOCK_EX)) {
            ftruncate($fp, 0);
            // Header
            fwrite($fp, view('feeds/avitoHeader', [], $viewOptions));
            // Body
            $step = 500;
            $offset = 0;
            do {
                $products = $this->productModel->getProducts(
                    [
                        'location'  => $this->locationId,
                        'price'     => $this->priceId,
                        'published' => 1,
                        /*'available' => 1, */ // TEMP until import from 1C starts working
                        'type'      => 115, // TODO
                        /*'features'  => [5 => [4451, 4487, 4469]]*/
                    ],
                    limit: [$offset => $step],
                    fields: ['features', 'price', 'pictures', 'promotions', 'storage']
                );
                $offset += $step;

                foreach ($products as $product) {
                    if (!isset($product->pictures[0]->variations->product_large) || !$product->qty) // TEMP remove $product->qty when 1C gives us the correct available status
                        continue;

                    //TEMP check available status from old db
                    $result = $this->productModel->db->query(
                        "select 1 
                         from blacktyres.bt_tires_var_loc tvl 
                         where tvl.product_id = {$product->obsolete_product_id}
                            and tvl.location_id = {$this->locationId}
                            and tvl.status = 1"
                    )->getResult('array');
                    if (count($result) === 0)
                        continue;

                    fwrite($fp, view('feeds/avitoOffer', [
                        'product' => $product,
                        'pickups' => $pickups,
                        'baseUrl' => substr(config('App')->baseURL, 0, -1)
                    ], $viewOptions));
                }
            } while ($products);
            // Footer
            fwrite($fp, view('feeds/avitoFooter', [], $viewOptions));
        }
        fclose($fp);
    }

}
