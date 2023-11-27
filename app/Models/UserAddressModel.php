<?php

namespace App\Models;

use App\Entities\UserAddress;

class UserAddressModel extends BaseModel
{
    protected $table            = 'users_addresses';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = UserAddress::class;
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'user_id',
        'address',
        'address_json',
        'used_at',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules = [
        'user_id' => 'required',
        'address' => 'required',
    ];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    /**
     * Basic query to find the User address.
     * Works with the current query builder instance.
     *
     * @param array<string, string> $filter  [id, user, name]
     *                                       A set of filters as an array <db field alias> => <db value>.
     * @param array<string, string> $sort    [usedAt]
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
            {$this->table}.user_id,
            {$this->table}.address,
            {$this->table}.address_json       
        ");

        // filter
        if (array_key_exists('id', $filter)) {
            $builder->where("{$this->table}.id", $filter['id']);
        }
        if (array_key_exists('user', $filter)) {
            $builder->where("{$this->table}.user_id", $filter['user']);
        }
        if (array_key_exists('name', $filter)) {
            $builder->where("{$this->table}.address", $filter['name']);
        }

        // sort
        foreach ($sort as $order => $direction) {
            switch ($order) {
                case 'usedAt': $builder->orderBy("{$this->table}.used_at", $direction); break;
            }
        }

        // limit
        $this->queryLimit($limit);

        return $this;
    }

}
