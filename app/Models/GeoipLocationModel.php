<?php

namespace App\Models;

use App\Entities\GeoipLocation;

class GeoipLocationModel extends BaseModel
{
    protected $DBGroup          = 'old';
    protected $table            = 'geoip_location';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = GeoipLocation::class;
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'id',
        'parent_id',
        'name',
        'name_p',
        'name_d',
        'name_utm',
        'url',
        'code',
        'url_rewriting',
        'type',
        'ext_id',
        'iso_code',
        'active',
        'hidden',
        'sort',
    ];

    // Dates
    protected $useTimestamps = false;

    // Validation
    protected $validationRules      = [];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    /**
     * Basic query to find the Locations.
     * Works with the current query builder instance.
     *
     * @param array<string, string> $filter  [id, published, type]
     *                                       A set of filters as an array <db field alias> => <db value>.
     * @param array<string, string> $sort    [name, sort]
     *                                       A set of sort field aliases as an array <db field alias> => <order direction(desc|asc)>.
     * @param array<int, int>       $limit   One-dimensional array <offset> => <limit> to limit the result set.
     * @param array<string, string> $options [hints[noCache, calcRows, distinct]]
     *                                       A set of additional query settings as an array <setting name> => <setting value>.
     * @param array                 $fields  [] - extra fields to be returned to the result set.
     *
     * @return $this
     */
    public function query(array $filter, array $sort = [], array $limit = [0 => 1], array $options = [], array $fields = []): static
    {
        $builder = $this->builder();

        // query hints
        $this->queryHints($options['hints'] ?? []);

        // select
        $builder->select("
                {$this->table}.id,
                {$this->table}.parent_id,
                {$this->table}.code,
                JSON_OBJECT('value', {$this->table}.name, 'dative', {$this->table}.name_d, 'prepositional', {$this->table}.name_p, 'utm', {$this->table}.name_utm) name,
                {$this->table}.url,
                {$this->table}.active published,
                {$this->table}.hidden,
                {$this->table}.iso_code,
                {$this->table}.sort
            ");

        // filter
        if ($filter['id'] ?? false) {
            $builder->where("{$this->table}.id", $filter['id']);
        }
        if ($filter['published'] ?? false) {
            $builder->where("{$this->table}.active", 1);
        }
        if ($filter['type'] ?? false) {
            $builder->where("{$this->table}.type", $filter['type']);
        }

        // sort
        foreach ($sort as $order => $direction) {
            switch ($order) {
                case 'sort': $builder->orderBy("{$this->table}.sort", $direction); break;
                case 'name': $builder->orderBy("{$this->table}.name", $direction); break;
            }
        }

        // limit
        $this->queryLimit($limit);

        return $this;
    }
}
