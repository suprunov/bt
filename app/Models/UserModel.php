<?php

namespace App\Models;

use App\Entities\User;
use CodeIgniter\Database\Exceptions\DataException;
use CodeIgniter\Shield\Exceptions\ValidationException;
use CodeIgniter\Shield\Models\CheckQueryReturnTrait;
use CodeIgniter\Shield\Models\UserModel as ShieldUsers;
use Bonfire\Users\Models\UserModel as BonfireUserModel;

class UserModel extends BonfireUserModel
{
    use CheckQueryReturnTrait;
    use BaseModelTrait;

    protected $returnType    = User::class;
    protected $allowedFields = [
        // defaults
        'username',
        'status',
        'status_message',
        'active',
        'last_active',
        'deleted_at',
        'avatar',
        'first_name',
        'last_name',
        // extra
        'published',
        'blocked',
        'name',
        'middle_name',
        'legal_status',
        'inn',
        'birth_date',
        'gender',
        'passport',
        'phone',
        'phone_notify',
        'email_main',
        'email_verified',
        'email_notify',
        'price_type_id',
        'last_location_id',
        'location_id',
        'legal_data',
        'guid',
    ];

    //protected $afterInsert   = ['updateEmailIdentity'];
   /* protected $afterInsert   = ['saveIdentities'];
    protected $afterUpdate   = ['saveIdentities'];*/

    // Validation
    protected $validationRules      = [];

    /**
     * Override the BaseModel's `update()` method.
     * If you pass User object, also updates Email and Phone Identities.
     *
     * @param array|int|string|null $id
     * @param array|User            $data
     *
     * @throws ValidationException
     */
   /* public function update1($id = null, $data = null): bool
    {
        $this->tempUser = $data instanceof User ? $data : null;

        try {
            $result = ShieldUsers::update($id, $data);
        } catch (DataException $e) {
            $messages = [
                lang('Database.emptyDataset', ['update']),
            ];

            if (in_array($e->getMessage(), $messages, true)) {
                $this->tempUser->saveEmailIdentity();
                $this->tempUser->savePhoneIdentity();

                return true;
            }

            throw $e;
        }

        $this->checkQueryReturn($result);

        return true;
    }*/

    /**
     * Save Identities (phone and email)
     *
     * Model event callback called by `afterInsert` and `afterUpdate`.
     */
    /*protected function saveIdentities1(array $data): array
    {
        // If insert()/update() gets an array data, do nothing.
        if ($this->tempUser === null) {
            return $data;
        }

        // Insert
        if ($this->tempUser->id === null)
        {
            $userId = $this->db->insertID();

            $user = $this->find($userId);

            $this->tempUser->id = $userId;

            $user->email         = $this->tempUser->email ?? '';
            $user->password      = $this->tempUser->password ?? '';
            $user->password_hash = $this->tempUser->password_hash ?? '';
            $user->saveEmailIdentity();

            $user->phone         = $this->tempUser->phone ?? '';
            $user->phone_code    = $this->tempUser->phoneCode ?? '';
            $user->savePhoneIdentity();

            $this->tempUser = null;

            return $data;
        }

        // Update
        $this->tempUser->saveEmailIdentity();
        $this->tempUser->savePhoneIdentity();
        $this->tempUser = null;

        return $data;
    }*/

    /**
     * Save Identities (phone and email)
     *
     * Model event callback called by `afterInsert` and `afterUpdate`.
     */
   /* protected function updateEmailIdentity1(array $data): array
    {
        // If insert()/update() gets an array data, do nothing.
        if ($this->tempUser === null) {
            return $data;
        }

        // Insert
        if ($this->tempUser->id === null)
        {
            $userId = $this->db->insertID();

            $user = $this->find($userId);

            $this->tempUser->id = $userId;

            $user->email         = $this->tempUser->email ?? '';
            $user->password      = $this->tempUser->password ?? '';
            $user->password_hash = $this->tempUser->password_hash ?? '';
            $user->saveEmailIdentity();

            $user->phone         = $this->tempUser->phone ?? '';
            $user->phone_code    = $this->tempUser->phoneCode ?? '';
            $user->savePhoneIdentity();

            $this->tempUser = null;

            return $data;
        }

        // Update
        $this->tempUser->saveEmailIdentity();
        $this->tempUser->savePhoneIdentity();
        $this->tempUser = null;

        return $data;
    }*/

    /**
     * Basic query to find the User.
     * Works with the current query builder instance.
     *
     * @param array<string, string> $filter  [id, phone, blocked, active, email, emailVerified, inn]
     *                                       A set of filters as an array <db field alias> => <db value>.
     * @param array<string, string> $sort    []
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
            {$this->table}.name,
            {$this->table}.first_name,
            {$this->table}.last_name,
            {$this->table}.middle_name,
            {$this->table}.price_type_id,
            {$this->table}.active,
            {$this->table}.blocked,
            {$this->table}.phone,
            {$this->table}.phone_notify,
            {$this->table}.email_main,
            {$this->table}.email_verified,
            {$this->table}.email_notify,
            {$this->table}.birth_date,
            {$this->table}.gender,
            {$this->table}.passport,
            {$this->table}.location_id,
            {$this->table}.legal_status,
            {$this->table}.inn,
            {$this->table}.legal_data,
            {$this->table}.deleted_at,
            {$this->table}.guid
        ");

        // filter
        if (array_key_exists('id', $filter)) {
            $builder->where("{$this->table}.id", $filter['id']);
        }
        if (array_key_exists('phone', $filter)) {
            if (is_array($filter['phone'])) {
                $builder->whereIn("{$this->table}.phone", $filter['phone']);
            } else {
                $builder->where("{$this->table}.phone", $filter['phone']);
            }
        }
        if (array_key_exists('active', $filter)) {
            $builder->where("{$this->table}.active", $filter['active']);
        }
        if (array_key_exists('blocked', $filter)) {
            $builder->where("{$this->table}.blocked", $filter['blocked']);
        }
        if (array_key_exists('email', $filter)) {
            $builder->where("{$this->table}.email_main", $filter['email']);
        }
        if (array_key_exists('emailVerified', $filter)) {
            $builder->where("{$this->table}.email_verified", $filter['emailVerified']);
        }
        if (array_key_exists('inn', $filter)) {
            $builder->where("{$this->table}.inn", $filter['inn']);
        }

        // sort
        /*foreach ($sort as $order => $direction) {
            switch ($order) {
                case 'name': $builder->orderBy("{$this->table}.name", $direction); break;
            }
        }*/

        // limit
        $this->queryLimit($limit);

        return $this;
    }

    /**
     * Locate a User object by the given credentials.
     *
     * @param array<string, string> $credentials
     */
    /*public function findByCredentials1(array $credentials): ?User
    {
        // Phone is stored in an identity
        $phone = $credentials['phone'] ?? null;
        if ($phone) {
            $user = $this->findByFilter(['phone' => $phone], [1 => 0], null, ['phone', 'phone_password'])->getFirstRow('array');
            return (new User())->fill($user);
        }

        return parent::findByCredentials($credentials);
    }*/

    /**
     * TEMP old method of exporting user data to XML.
     *
     * @param int $userId
     * @param array $extraFields
     * @return void
     */
    public function importUserToXML(int $userId, array $extraFields = []): void
    {
        $user = $this->withDeleted()->find($userId);
        if ($user === null)
            return;

        helper('common');

        $userContactPersons = model(\App\Models\UserContactPersonModel::class)
            ->withDeleted()
            ->query(['user' => $user->id], limit:[])
            ->findAll();
        $userAddresses = model(\App\Models\UserAddressModel::class)
            ->query(['user' => $user->id], limit:[])
            ->findAll();

        $contactXML = "<contacts>\n";
        foreach($userContactPersons as $contact) {
            $contactXML .= '            <row>
            <contact_firstname>' . esc($contact->first_name) . '</contact_firstname>
            <contact_lastname>' . esc($contact->last_name) . '</contact_lastname>
            <contact_phone>' . \App\Helpers\PhoneHelper::format('', $contact->phone, ['type' => 'format_7']) . '</contact_phone>
            <contact_id>' . $contact->guid . '</contact_id>
            <contact_id_site>' . $contact->id . '</contact_id_site>
            <deleted>' . ($contact->deleted_at ? 'true' : 'false') . '</deleted>
        </row>
';
        }
        $contactXML .= '        </contacts>';

        $addressXML = "<shipping_addresses>\n";
        foreach($userAddresses as $address) {
            $addressData = new \App\Libraries\DaData\Address(null, json_decode($address->address_json, true) ?? []); // TODO

            $addressXML .='             <shipping_address>
                <name>' . esc($address->address) . '</name>
                <index_adress>' . esc($addressData->postalCode) . '</index_adress>
                <country>' . esc($addressData->country) . '</country>
                <region>' . esc($addressData->regionWithType) . '</region>
                <district>' . esc($addressData->areaWithType) . '</district>
                <city>' . esc($addressData->city) . '</city>
                <city_smal>' . esc($addressData->settlementWithType) . '</city_smal>
                <street>' . esc($addressData->streetWithType) . '</street>
                <haus>' . esc($addressData->house) . '</haus>
                <type_haus>' . esc($addressData->houseType) . '</type_haus>
                <corpus>' . esc($addressData->block) . '</corpus>
                <type_corpus>' . esc($addressData->blockType) . '</type_corpus>
                <apartment>' . esc($addressData->flat) . '</apartment>
                <type_apartment>' . esc($addressData->flatType) . '</type_apartment>
            </shipping_address>
';
        }
        $addressXML .= '        </shipping_addresses>';

        if ($user->legal_status == 'yur') {
            $xml = '<?xml version="1.0" encoding="UTF-8"?>
<clients>
    <client_company_new xmlns="'. config('App')->baseURL . '" xmlns:xs="//www.w3.org/2001/XMLSchema" xmlns:xsi="//www.w3.org/2001/XMLSchema-instance">
        <client_id>' . $user->guid . '</client_id>
        <client_id_site>' . $user->id . '</client_id_site>
        <legal_status>ur</legal_status>
        <company_name>' . esc($user->name) . '</company_name>
        <phone>' . \App\Helpers\PhoneHelper::format('', $user->phone, ['type' => 'format_7']) . '</phone>
        <email>' . esc($user->email_main) . '</email>
        <email_verified>' . ($user->email_verified ? 'true' : 'false') . '</email_verified>
        <sms_notify>' . ($user->phone_notify ? 'true' : 'false') . '</sms_notify>
        <email_notify>' . ($user->email_notify ? 'true' : 'false') . '</email_notify>
        <blocked>' . ($user->blocked ? 'true' : 'false') . '</blocked>
        <deleted>' . ($user->deleted_at ? 'true' : 'false') . '</deleted>
        <business_region_code>' . $user->location_id . '</business_region_code>
        <inn>' . esc($user->inn) . '</inn>
        <kpp>' . esc($user->legal_data->kpp) . '</kpp>
        <okpo>' . esc($user->legal_data->okpo) . '</okpo>
        <form_address>
            <name>' . esc($user->legal_data->address->name ?? '') . '</name>
            <index_adress>' . esc($user->legal_data->address->postalCode ?? '') . '</index_adress>
            <country>' . esc($user->legal_data->address->country ?? '') . '</country>
            <region>' . esc($user->legal_data->address->regionWithType ?? '') . '</region>
            <district>' . esc($user->legal_data->address->areaWithType ?? '') . '</district>
            <city>' . esc($user->legal_data->address->city ?? '') . '</city>
            <city_smal>' . esc($user->legal_data->address->settlementWithType ?? '') . '</city_smal>
            <street>' . esc($user->legal_data->address->streetWithType ?? '') . '</street>
            <haus>' . esc($user->legal_data->address->house ?? '') . '</haus>
            <type_haus>' . esc($user->legal_data->address->houseType ?? '') . '</type_haus>
            <corpus>' . esc($user->legal_data->address->block ?? '') . '</corpus>
            <type_corpus>' . esc($user->legal_data->address->blockType ?? '') . '</type_corpus>
            <apartment>' . esc($user->legal_data->address->flat ?? '') . '</apartment>
            <type_apartment>' . esc($user->legal_data->address->flatType ?? '') . '</type_apartment>
        </form_address>
        ' . $contactXML . '
        ' . $addressXML . '
    </client_company_new>
</clients>';

        } else { // fiz

            // Passport data
            $extra_fields_XML = "";
            foreach($extraFields as $exfield_name=>$exfield_val){
                $extra_fields_XML .= "\t\t\t<$exfield_name>".$exfield_val."</$exfield_name>\n";
            }

            $xml = '<?xml version="1.0" encoding="UTF-8"?>
<clients>
    <client_new xmlns="' . config('App')->baseURL .'" xmlns:xs="//www.w3.org/2001/XMLSchema" xmlns:xsi="//www.w3.org/2001/XMLSchema-instance">
        <client_id>' . $user->guid . '</client_id>
        <client_id_site>' . $user->id . '</client_id_site>
        <legal_status>fiz</legal_status>
        <blocked>' . ($user->blocked ? 'true' : 'false') . '</blocked>
        <deleted>' . ($user->deleted_at ? 'true' : 'false') . '</deleted>
        <phone>' . \App\Helpers\PhoneHelper::format('', $user->phone, ['type' => 'format_7']) . '</phone>
        <email>' . esc($user->email_main) . '</email>
        <email_verified>' . ($user->email_verified ? 'true' : 'false') . '</email_verified>
        <business_region_code>' . $user->location_id . '</business_region_code>
        <firstname>' . esc($user->first_name) . '</firstname>
        <lastname>' . esc($user->last_name) . '</lastname>
        <secondname>' . esc($user->middle_name) . '</secondname>
        <date_born>' . $user->birth_date . '</date_born>
        <sex>' . $user->gender . '</sex>
' . $extra_fields_XML . '			<sms_notify>' . ($user->phone_notify ? 'true' : 'false') . '</sms_notify>
        <email_notify>' . ($user->email_notify ? 'true' : 'false') . '</email_notify>
        ' . $contactXML . '
        ' . $addressXML . '
    </client_new>
</clients>';
        }

        $legalStatus = $user->legal_status === 'yur' ? 'ur' : 'fiz';
        file_put_contents(FCPATH . "import_files/profile_site/profile_{$legalStatus}_{$user->id}.xml", $xml);
    }

}
