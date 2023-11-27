<?php

namespace App\Models;

use App\Entities\Product;
use CodeIgniter\Database\BaseBuilder;

class ProductModel extends BaseModel
{
    protected $table            = 'products';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = Product::class;
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'name',
        'code',
        'product_code',
        'type_id',
        'kind',
        'published',
        'vendor_code',
        'unit',
        'bonus_points_apply',
        'novelty',
        'sort',
        'description',
        'obsolete_product_id',
        'guid',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // Validation
    protected $validationRules      = [
        'name'         => 'required',
        'code'         => 'required',
        'product_code' => 'required',
        'type_id'      => 'required',
        'kind'         => 'required|in_list[product,service]',
        'guid'         => 'required',
    ];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    /**
     * Basic query for Product search.
     * Works with the current query builder instance.
     *
     * @param array<string, string> $filter  [published, type, location, available, visible, promotions, receiving,
     *                                        features, price, priceMin, priceMax, diffWide, emptyDescription,
     *                                        searchQuery, productCode, model]
     *                                       A set of filters as an array <db field alias> => <db value>.
     * @param array<string, string> $sort    [available, popularity, novelty, price]
     *                                       A set of sort field aliases as an array <db field alias> => <order direction(desc|asc)>.
     * @param array<int, int>       $limit   One-dimensional array <offset> => <limit> to limit the result set.
     * @param array<string, string> $options [hints[noCache, calcRows, distinct], totals]
     *                                       A set of additional query settings as an array <setting name> => <setting value>.
     * @param array                 $fields  A simple array of database field aliases to be returned to the result set.
     *
     * @return $this
     */
    public function query(array $filter, array $sort = [], array $limit = [0 => 1], array $options = [], array $fields = ['id']): static
    {
        $builder = $this->builder();

        // query hints
        $this->queryHints($options['hints'] ?? []);

        // select
        if ($options['totals'] ?? false) { // totals select
            $builder->select('COUNT(*) total', false);
            if ($filter['price'] ?? false) {
                $builder->select('IFNULL(MIN(pp.value),0) price_min, IFNULL(MAX(pp.value),0) price_max', false);
            }
        } else { // regular select
            $this->querySelect($fields);
        }

        // simple filters
        if (isset($filter['published'])) {
            $builder->where("{$this->table}.published", (int)$filter['published']);
        }
        if ($filter['type'] ?? false) {
            if (is_array($filter['type']))
                $builder->whereIn("{$this->table}.type_id", $filter['type']);
            else
                $builder->where("{$this->table}.type_id", $filter['type']);
        }
        if ($filter['productCode'] ?? false) {
            $builder->where("{$this->table}.product_code", $filter['productCode']);
        }
        if ($filter['model'] ?? false) {
            $builder->where("{$this->table}.model_id", $filter['model']);
        }
        if ($filter['emptyDescription'] ?? false) {
            $builder->where("{$this->table}.description", '');
        }
        if ($filter['searchQuery'] ?? false) { // TEMP later it'll be in the search model
            $builder->like("{$this->table}.name", $filter['searchQuery']);
        }

        // location
        if ($filter['location'] ?? false)
        {
            $builder->join(tbl('products_locations') . ' pl', "pl.product_id = {$this->table}.id")
                ->where('pl.location_id', $filter['location']);

            if (isset($filter['available']))
                $builder->where('pl.available', (int)$filter['available']);

            if (isset($filter['visible']))
                $builder->where('pl.visible', (int)$filter['visible']);

            // promotions // TODO if exists
            if ($filter['promotions'] ?? false) {
                $builder->join(tbl('products_promotions', 'old') . " prom", "prom.product_id = {$this->table}.id")
                    ->where('prom.location_id', $filter['location'])
                    ->whereIn('prom.promotion_id', $filter['promotions']);
            }

            // delivery
            if ($filter['delivery'] ?? false) {
                $delivery      = $filter['delivery'];
                $deliveryType  = $delivery['deliveryType'] ?? null;
                $deliveryId    = $delivery[$deliveryType] ?? null;
                $deliveryDays  = ! empty($delivery['days']) ? max($delivery['days']) : null;
                $deliveryDaysCondition = null;
                if ($deliveryDays) {
                    switch ($deliveryDays) {
                        case 1:  $deliveryHours = 0;  $sign = '='; break;
                        case 2:  $deliveryHours = 2;  $sign = '<='; break;
                        case 3:  $deliveryHours = 24; $sign = '<'; break;
                        case 4:  $deliveryHours = 48; $sign = '<'; break;
                        case 5:  $deliveryHours = 72; $sign = '<'; break;
                        default: $deliveryHours = 72; $sign = '>='; break;
                    }
                    $deliveryDaysCondition =  $sign . ' ' . $deliveryHours;
                }

                if ($deliveryId) {

                    $subBuilder = $this->db->table(tbl('products_deliveries') . ' pd')
                        ->select('1', false)
                        ->where("pd.type", $deliveryType)
                        ->where("pd.product_id = {$this->table}.id")
                        ->where("pd.location_id", $filter['location'])
                        ->where("pd.delivery_id", $deliveryId)
                        ->limit(1);

                    if ($deliveryDaysCondition)
                        $subBuilder->where("pd.hours {$deliveryDaysCondition}");

                } elseif ($deliveryDaysCondition) {

                    $subBuilder = $this->db->table(tbl('products_deliveries_grouped') . ' pdg')
                        ->select('1', false)
                        ->where("pdg.product_id = {$this->table}.id")
                        ->where("pdg.location_id", $filter['location'])
                        ->where("pdg.hours {$deliveryDaysCondition}")
                        ->limit(1);
                    if ($deliveryType)
                        $subBuilder->where("pdg.type", $deliveryType);
                }

                if ($subBuilder ?? false) {
                    $subQuery = $subBuilder->getCompiledSelect();
                    $builder->where('EXISTS (' . $subQuery . ')', null, false)
                        ->where('pl.available', 1);
                }
            }
        }

        // features
        if (($filter['features'] ?? false) && is_array($filter['features'])) {
            foreach ($filter['features'] as $featureId => $valueId) {
                $builder->join(tbl('products_features_values') . " pfv_{$featureId}", "pfv_{$featureId}.product_id = {$this->table}.id")
                    ->where("pfv_{$featureId}.feature_id", $featureId);

                if (is_array($valueId)) {
                    $builder->whereIn("pfv_{$featureId}.value_id", $valueId);
                } else {
                    $builder->where("pfv_{$featureId}.value_id", $valueId);
                }

                if (array_key_exists('type', $filter))
                    $builder->where("pfv_{$featureId}.type_id", $filter['type']);
            }
        }

        // prices
        if ($filter['price'] ?? false) {
            if (isset($filter['priceMin']) || isset($filter['priceMax']) || isset($sort['price'])) {
                $builder->join(
                    tbl('products_prices') . ' pp',
                    "pp.product_id = {$this->table}.id AND pp.price_id = '{$filter['price']}'",
                    'left'
                );
            }
            if (isset($filter['priceMin']))
                $builder->where('pp.value >', $filter['priceMin']);
            if (isset($filter['priceMax']))
                $builder->where('pp.value <', $filter['priceMax']);
        }

        // sort and limit
        if (! ($options['totals'] ?? false)) {
            // sort
            foreach ($sort as $field => $direction) {
                switch ($field) {
                    case 'available':
                        if ($filter['location'] ?? false)
                            $builder->orderBy('pl.available', $direction);
                        break;
                    case 'popularity': $builder->orderBy("{$this->table}.sort", $direction); break;
                    case 'novelty':    $builder->orderBy("{$this->table}.novelty", $direction); break;
                    case 'price':
                        if ($filter['price'] ?? false)
                            $builder->orderBy('pp.value', $direction);
                        break;
                }
            }
            // limit
            $this->queryLimit($limit);
        }

        // different wide // TODO
        if ($filter['diffWide'] ?? false) {
            $modelFeatureId = 3; // TODO
            $builder->join(tbl('products_features_values') . " pfv_{$modelFeatureId}", "pfv_{$modelFeatureId}.product_id = {$this->table}.id")
                ->where("pfv_{$modelFeatureId}.feature_id", $modelFeatureId);

            if (array_key_exists('type', $filter))
                $builder->where("pfv_{$modelFeatureId}.type_id", $filter['type']);

            // append a "different wide" query part to the main query by copying the main query
            $this->appendDiffWideQuery($filter['diffWide'], $options['totals'] ?? false);

            // tie diff_wide query to the main query
            $builder->where("pfv_{$modelFeatureId}.value_id = pfv_{$modelFeatureId}_diff.value_id")
                ->where("{$this->table}.id != p_diff.id");
        }

        return $this;
    }

    /**
     * Append a "different wide" query part to the main query by copying the main query
     * and replacing the values of the features involved in the "different wide" part.
     *
     * @param array $features Features involved in the "different wide"
     * @param bool $totals Does the main query count the totals
     *
     * @return void
     */
    private function appendDiffWideQuery(array $features, bool $totals): void // TODO
    {
       /* // recreate select
        $selectArr = $this->db->ar_select;
        $this->db->ar_select = [];
        foreach ($selectArr as $select) {
            $select = preg_replace(array('~`~', '~([a-z][\da-z_]*)\.([a-z][\da-z_]*)~'), array('', '$1.$2 ' . ($totals ? '+' : ',') . ' $1_diff.$2'), $select);
            $this->db->select($select);
        }

        // add joins
        foreach (array_merge($this->db->ar_from, $this->db->ar_join) as $join) {
            $joinArr = preg_split('~ ON ~', $join);
            $joinType = preg_split('~JOIN~', $joinArr[0]);
            $joinType = isset($joinType[1]) ? $joinType[0] : null;
            $joinArr[0] = preg_replace(array('~^(LEFT |RIGHT )?JOIN ~', '~([a-z][\da-z_]*)$~'), array('', '$1_diff'), $joinArr[0]);
            $joinArr[1] = isset($joinArr[1]) ?
                preg_replace('~([a-z][\da-z_]*)\.([a-z][\da-z_]*)~', '$1_diff.$2', $joinArr[1]) : '1';
            // exclude those involved in the diff_wide but whose values are null
            foreach ($features as $featureId => $valueId) {
                if ($valueId === null && preg_match("~pfv_{$featureId}~", $joinArr[0])) {
                    continue 2;
                }
            }
            $this->db->join($joinArr[0], $joinArr[1], $joinType);
        }

        // add wheres
        foreach ($this->db->ar_where as $where) {
            $where = preg_replace(array('~^AND ~', '~`~'), array('', ''), $where);
            foreach ($features as $featureId => $valueId) {
                if (preg_match("~pfv_{$featureId}~", $where)) {
                    if ($valueId === null) {
                        // exclude those involved in the diff_wide but whose values are null
                        continue 2;
                    } elseif (preg_match("~pfv_{$featureId}.value_id~", $where)) {
                        // replace feature value with a new value
                        $where = "pfv_{$featureId}.value_id IN ('{$valueId}')";
                    }
                }
            }
            $where = preg_replace('~([a-z][\da-z_]*)\.([a-z][\da-z_]*)~', '$1_diff.$2', $where);
            $this->db->where($where);
        }

        // recreate orderby
        $orderByArr = $this->db->ar_orderby;
        $this->db->ar_orderby = [];
        foreach ($orderByArr as $orderBy) {
            $orderBy = preg_replace(array('~`~', '~([a-z][\da-z_]*)\.([a-z][\da-z_]*)~'), array('', '$1.$2 + $1_diff.$2'), $orderBy);
            $this->db->orderby($orderBy);
        }*/
    }

    /**
     * Basic query for Extended product data (main fields + characteristics).
     * Works with the current query builder instance.
     *
     * @param array $filter  [id, location, price]
     * @param array $sort    [[order => direction]]
     * @param array $limit   [offset => limit]
     * @param array $options [hints[]]
     * @param array $fields  [features, pictures, promotions, storage, price, description, brand, model, reviewTotals]
     *                       A simple array of extra fields to be returned to the result set.
     * @return $this
     */
    public function queryExtended(array $filter, array $sort = [], array $limit = [0 => 1], array $options = [], array $fields = []): static
    {
        $builder = $this->builder();

        // Convert $fields array to a convenient format.
        $fields = array_fill_keys($fields, true);

        // query hints
        $this->queryHints($options['hints'] ?? []);

        // select - main
        $builder->select("
                {$this->table}.id, 
                {$this->table}.name, 
                {$this->table}.code,
                {$this->table}.product_code, 
                {$this->table}.kind,  
                {$this->table}.bonus_points_apply,
                {$this->table}.vendor_code, 
                {$this->table}.unit, 
                {$this->table}.novelty, 
                {$this->table}.obsolete_product_id, 
                {$this->table}.published, 
                {$this->table}.guid, 
                JSON_OBJECT('id', {$this->table}.type_id, 'code', pt.code, 'name', pt.name, 'name_plural', pt.name_plural, 'obsolete_type_id', pt.obsolete_type_id) type,
                " . (isset($fields['description']) ? "{$this->table}.description," : "") . "
                pt.qty_set"
            )
            ->join(tbl('products_types') . ' pt', "{$this->table}.type_id = pt.id");

        // filter - id
        if ($filter['id'] ?? false) {
            if (is_array($filter['id']))
                $builder->whereIn("{$this->table}.id", $filter['id']);
            else
                $builder->where("{$this->table}.id", $filter['id']);
        }

        // filter - location
        if ($filter['location'] ?? false ) {
            $builder->select('pl.available, pl.updated_at as location_updated_at') // TODO why do we need location_updated_at ?
                ->join(tbl('products_locations') . ' pl', "pl.product_id = {$this->table}.id")
                ->where('pl.location_id', $filter['location']);

            // filter - prices
            if ($fields['price'] ?? false) {
                $builder->select("JSON_OBJECT('value', pp_def.value, 'bonus_points', pp_def.bonus_points, 'updated_at', pp_def.updated_at) price_default") // TODO why do we need price_updated_at? // TODO old price
                    ->join(tbl('prices') . ' pr', "pr.location_id = '{$filter['location']}' AND pr.default = 1", 'left')
                    ->join(tbl('products_prices') . ' pp_def', "pp_def.product_id = {$this->table}.id AND pp_def.price_id = pr.id", 'left');
                if ($filter['price'] ?? false) {
                    $builder->select("JSON_OBJECT('value', pp.value, 'bonus_points', pp.bonus_points, 'updated_at', pp.updated_at) price") // TODO why do we need price_updated_at? // TODO old price
                    ->join(tbl('products_prices') . ' pp', "pp.product_id = {$this->table}.id AND pp.price_id = '{$filter['price']}'", 'left');
                }
            }

            // select - storage
            if ($fields['storage'] ?? false) {
                $subQueryStorageBuilder = $this->db->table(tbl('products_storages') . ' ps')
                    ->join(tbl('storages_locations') . ' sl', 'sl.storage_id = ps.storage_id')
                    ->select("IFNULL(SUM(ps.qty), 0) qty")
                    ->where("ps.product_id = {$this->table}.id")
                    ->where('sl.location_id', $filter['location']);
                $builder->selectSubquery($subQueryStorageBuilder, 'qty');
            }

            // select - promotions // TODO auth field
            if ($fields['promotions'] ?? false)
            {
                $subQueryPromotionsBuilder = $this->db->table(tbl('promotions', 'old') . ' prom')
                    ->join(tbl('products_promotions') . ' p_prom', "p_prom.promotion_id = prom.id")
                    ->select("JSON_ARRAYAGG(DISTINCT JSON_OBJECT('id', prom.id, 'name', prom.title))") // TODO TEMP remove distinct
                    ->where("p_prom.product_id = {$this->table}.id")
                    ->where("p_prom.location_id", $filter['location']);

                $builder->selectSubquery($subQueryPromotionsBuilder, 'promotions');
            }
        }

        // select - features
        if ($fields['features'] ?? false)
        {
            // TODO move to featureValueModel ?!
            $subQueryFeaturesBuilder = $this->db->table(tbl('features_filters') . ' ff');

            $subQueryFeaturesBuilder->select("
                JSON_ARRAYAGG(JSON_OBJECT(
                    'id', f.id,
                    'code', f.code,
                    'type', f.type,
                    'name', ff.name,
                    'url_template', ff.url_template,
                    'url_sort', ff.url_sort,
                    'card_published', ff.card_published,
                    'card_sort', ff.card_sort,
                    'card_group_id', ff.card_group_id,
                    'card_similar', ff.card_similar,
                    'preview_published', ff.preview_published,
                    'preview_sort', ff.preview_sort,
                    /* 'filter_published', ff.filter_published,
                    'filter_sort', ff.filter_sort,
                    'filter_type', ff.filter_type,
                    'filter_value_separator', ff.filter_value_separator,
                    'filter_no_index', ff.filter_no_index, */
                    'description', ff.description,
                    'value_id', fv.id,
                    'value', fv.value,
                    'value_double', fv.value_double,
                    'value_alias', fv.value_alias,
                    'value_code', fv.code,
                    'value_description', fv.description
                )) features
            ");

            $subQueryFeaturesBuilder->join(tbl('features') . ' f', 'ff.feature_id = f.id')
                ->join(tbl('products_features_values') . ' pfv', "pfv.product_id = {$this->table}.id AND pfv.type_id = {$this->table}.type_id AND pfv.feature_id = ff.feature_id", 'left')
                ->join(tbl('features_values') . ' fv', 'pfv.value_id = fv.id', 'left')
                ->where('ff.published', 1)
                ->where("ff.product_type_id = {$this->table}.type_id");

            $builder->selectSubquery($subQueryFeaturesBuilder, 'features');
        }

        // select - model
        if ($fields['model'] ?? false)
        {
            // TODO move to modelModel ?!
            $builder->select("
                JSON_OBJECT('id', model.id, 'code', fv_model.code, 'name', model.name, 'obsolete_model_id', model.obsolete_model_id) model
            ");
            $builder->join(tbl('features') . ' f_model', "f_model.reference = 'model'", 'left')
                ->join(tbl('products_features_values') . ' pfv_model', "pfv_model.product_id = {$this->table}.id AND pfv_model.feature_id = f_model.id", 'left')
                ->join(tbl('models') . ' model', 'model.id = pfv_model.value_id', 'left')
                ->join(tbl('features_values') . ' fv_model', 'fv_model.id = pfv_model.value_id', 'left');

            // select - review totals (qty, average grade)
            if ($fields['reviewTotals'] ?? false)
            {
                $subQueryReviewsBuilder = $this->db->table(tbl('models_reviews') . ' m_rev')
                    ->select("JSON_OBJECT('qty', COUNT(m_rev.id), 'average', ROUND(AVG(m_rev.grade), 1))")
                    ->where("m_rev.model_id = model.id")
                    ->groupBy("m_rev.model_id");

                // $builder->selectSubquery($subQueryReviewsBuilder, 'review_totals');
                $builder->select('COALESCE((' . $subQueryReviewsBuilder->getCompiledSelect() . "), JSON_OBJECT('qty', 0, 'average', 0)) review_totals", false);
            }
        }

        // select - brand
        /*if ($fields['brand'] ?? false)
        {
            $builder->select("
                JSON_OBJECT('id', brand.id, 'code', fv_brand.code, 'name', brand.name, 'obsolete_model_id', model.obsolete_model_id) brand
            ");
            $builder->join(tbl('products_features_values') . ' pfv_brand', "pfv_brand.product_id = {$this->table}.id")
                ->join(tbl('features') . ' f_brand', 'pfv_brand.feature_id = f_brand.id')
                ->join(tbl('brands') . ' brand', 'brand.id = pfv_brand.value_id')
                ->join(tbl('features_values') . ' fv_brand', 'fv_brand.id = pfv_brand.value_id')
                ->where('f_brand.reference', 'brand');
        }*/

        // select - pictures
        if ($fields['pictures'] ?? false)
        {
            // TODO move to productPictureModel
            $subQueryPicturesBuilder = $this->db->table(tbl('products_pictures') . ' ppic');
            $subQueryPicturesBuilder->select("
                JSON_ARRAYAGG(JSON_OBJECT(
                'id', picv.picture_id,
                'variation_id', picv.id,
                'type_code', pict.code,
                'variation_code', pictv.code,                
                'sort', ppic.sort,
                'path', picv.path,
                'filename', picv.filename,
                'extension', picv.extension,
                'width', picv.width,
                'height', picv.height)) 
            ", false);

            $subQueryPicturesBuilder->join(tbl('pictures') . ' pic', 'pic.id = ppic.picture_id')
                ->join(tbl('pictures_variations') . ' picv', 'picv.picture_id = pic.id')
                ->join(tbl('pictures_types_variations') . ' pictv', "pictv.id = picv.type_variation_id")
                ->join(tbl('pictures_types') . ' pict', 'pict.id = pictv.type_id')
                ->where("ppic.product_id = {$this->table}.id")
                ->orderBy('ppic.sort', 'asc');

            $builder->selectSubquery($subQueryPicturesBuilder, 'pictures');
        }

        // limit
        if (count($limit)) {
            $this->queryLimit($limit);
        }

        return $this;
    }

    /**
     * Generic query from "query" and "queryExtended".
     *
     * @param array $filter
     * @param array $sort
     * @param array $limit
     * @param array $options
     * @param array $fields
     *
     * @return $this
     *
     * @see ProductModel::query
     * @see ProductModel::queryExtended
     */
    public function queryProducts(array $filter, array $sort = [], array $limit = [0 => 1], array $options = [], array $fields = []): static
    {
        $builder = $this->builder;

        // Build a search sub-query
        $this->query($filter, $sort, $limit);
        $searchBuilder = clone $builder;
        $builder->resetQuery();

        // Build a sub-query containing extended product data
        $detailedFilter = array_filter([
            'location' => $filter['location'] ?? null,
            'price'    => $filter['price'] ?? null,
        ]);
        $this->queryExtended(filter: $detailedFilter, limit: [], fields: $fields);
        $detailedBuilder = clone $builder;
        $builder->resetQuery();

        // Build the final query.
        // query hints
        $this->queryHints($options['hints'] ?? []);
        // Union sub-queries
        $builder->select('detail.*')
            ->join("({$searchBuilder->getCompiledSelect()}) main", "{$this->table}.id = main.id", '', false)
            ->join("({$detailedBuilder->getCompiledSelect()}) detail", "detail.id = main.id", 'left', false);

        return $this;
    }

    /**
     * Get a list of Extended product data (main fields + characteristics).
     *
     * @param array $productIds An array of product ids (or objects with "id" property)
     * @param array $filter [id, location, price]
     * @param array $fields
     *
     * @return array
     *
     * @see ProductModel::queryExtended
     */
    public function getExtendedProducts(array $productIds = [], array $filter = [], array $fields = []): array
    {
        $products = [];

        foreach ($productIds as $productId) {
            $productId = is_object($productId) ? $productId->id : $productId;
            $products[$productId] = $productId;
        }

        if (count($products)) {
            // Build a sub-query containing extended product data
            $detailedFilter = array_filter([
                'id'       => $products,
                'location' => $filter['location'] ?? null,
                'price'    => $filter['price'] ?? null,
            ]);
            $productsFound = $this->queryExtended(filter:$detailedFilter,  limit:[], fields: $fields)->findItems();
            foreach ($productsFound as $product) {
                $products[$product->id] = $product;
            }
        }

        return $products;
    }

    /**
     * Get a list of Products (with extended data) in two steps using "query" and "queryExtended".
     *
     * @param array $filter
     * @param array $sort
     * @param array $limit
     * @param array $options
     * @param array $fields
     *
     * @return array
     *
     * @see ProductModel::query
     * @see ProductModel::queryExtended
     */
    public function getProducts(array $filter, array $sort = [], array $limit = [0 => 1], array $options = [], array $fields = []): array
    {
        // Get products IDs
        $productFound = $this->query($filter, $sort, $limit, $options)->findItems();

        return $this->getExtendedProducts($productFound, $filter, $fields);
    }

    /**
     * Get total sums for product search.
     *
     * @param array $filter
     * @param array $options
     *
     * @return $this
     *
     * @see ProductModel::query
     */
    public function getTotals(array $filter, array $options = []): static
    {
        // query hints
        $this->queryHints($options['hints'] ?? []);

        // Build the search sub-query
        $options['totals'] = true;
        $this->query($filter, options: $options);

        return $this;
    }

}
