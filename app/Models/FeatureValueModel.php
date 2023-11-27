<?php

namespace App\Models;

use App\Entities\FeatureValue;

class FeatureValueModel extends BaseModel
{
    protected $table            = 'features_values';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = FeatureValue::class;
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'reference',
        'feature_id',
        'value',
        'value_alias',
        'value_double',
        'code',
        'description',
        'filter_default',
        'filter_sort',
        'guid',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // Validation
    protected $validationRules      = [
        'reference' => 'required|in_list[none,miscellanea,brand,model,color,car_brand,color_group,country]',
        'feature_id' => 'required',
        /*'code' => 'required',*/
    ];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = [];
    protected $afterInsert    = [];
    protected $beforeUpdate   = [];
    protected $afterUpdate    = [];
    protected $beforeFind     = [];
    protected $afterFind      = [];
    protected $beforeDelete   = [];
    protected $afterDelete    = [];

    /**
     * Find Feature value by feature_id and GUID
     *
     * @param ?string $feature_id
     * @param ?string $guid
     *
     * @return array|object|null
     */
    public function findByFeatureGuid(?string $feature_id, ?string $guid)
    {
        if ($feature_id === null || $guid === null)
            return null;

        return $this->where('feature_id', $feature_id)
            ->where('guid', $guid)
            ->first();
    }

    /**
     * Find Feature value by feature_id and value
     *
     * @param ?string $feature_id
     * @param ?string $value
     *
     * @return array|object|null
     */
    public function findByFeatureValue(?string $feature_id, ?string $value)
    {
        if ($feature_id === null)
            return null;

        return $this->where('feature_id', $feature_id)
            ->where('guid', null)
            ->where('value', $value)
            ->first();
    }

}
