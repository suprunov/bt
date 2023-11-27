<?php

namespace App\Models;

use App\Entities\Contact;

class ContactModel extends BaseModel
{
    protected $table = 'contacts';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = Contact::class;
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'location_id',
        'phones',
        'emails',
        'schedules',
        'schedules_call',
        'sort',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    // Validation
    protected $validationRules = [
        'location_id' => 'required',
    ];
    protected $validationMessages = [];
    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    /**
     * Basic query to find the store contacts.
     * Works with the current query builder instance.
     *
     * @param array<string, string> $filter  [location]
     *                                       A set of filters as an array <db field alias> => <db value>.
     * @param array<string, string> $sort    [sort]
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
            {$this->table}.location_id,              
            {$this->table}.phones,              
            {$this->table}.emails,              
            {$this->table}.schedules,              
            {$this->table}.schedules_call,              
            {$this->table}.sort              
        ");

        // filter
        if ($filter['location'] ?? false) {
            $builder->where("{$this->table}.location_id", $filter['location']);
        }

        // sort
        foreach ($sort as $order => $direction) {
            switch ($order) {
                case 'sort': $builder->orderBy("{$this->table}.sort", $direction); break;
            }
        }

        // limit
        $this->queryLimit($limit);

        return $this;
    }

}
