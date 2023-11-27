<?php

namespace App\Controllers\Api\v1;

use App\Controllers\ApiController;
use App\Entities\Client;
use App\Entities\ClientAddress;
use App\Entities\ClientBankAccount;
use App\Entities\ClientCar;
use App\Entities\ClientStorageContract;
use App\Entities\ClientUser;
use App\Models\BankModel;
use App\Models\VehicleModelModel;
use App\Models\VehicleModificationModel;
use App\Models\ClientAddressModel;
use App\Models\ClientBankAccountModel;
use App\Models\ClientCarModel;
use App\Models\ClientModel;
use App\Models\ClientStorageContractModel;
use App\Models\ClientUserModel;
use App\Models\FeatureModel;
use App\Models\FeatureValueModel;
use App\Models\OldPickupModel;
use App\Models\PriceModel;
use App\Models\UserModel;

class ClientController extends ApiController
{
    protected ClientModel $clientModel;
    protected ClientUserModel $clientUserModel;
    protected ClientAddressModel $clientAddressModel;
    protected ClientBankAccountModel $clientBankAccountModel;
    protected ClientCarModel $clientCarModel;
    protected ClientStorageContractModel $clientStorageContractModel;

    public function __construct()
    {
        $this->clientModel = model(ClientModel::class);
        $this->clientUserModel = model(ClientUserModel::class);
        $this->clientAddressModel = model(ClientAddressModel::class);
        $this->clientBankAccountModel = model(ClientBankAccountModel::class);
        $this->clientCarModel = model(ClientCarModel::class);
        $this->clientStorageContractModel = model(ClientStorageContractModel::class);
    }

    /**
     * Create a new or update an existing Client
     * 
     * @param string $guid
     *
     * @return mixed
     */
    public function saveClient(string $guid)
    {
        $inputData = $this->request->getJSON();

        if (empty($inputData) || ! property_exists($inputData, 'users')) {
            return $this->failValidationErrors(lang('RESTful.invalidInput', ['']));
        }

        // Transaction start
        $this->clientModel->db->transStart();

        // Save Client
        $client = $this->clientModel->findByGuid($guid);
        if ($client === null) {
            $crud = 'create';
            $client = new Client;
        } else {
            $crud = 'update';
        }
        $client->guid = $guid;
        if (property_exists($inputData, 'price_guid')) {
            $price = model(PriceModel::class)->findByGuid($inputData->price_guid);
            if ($price === null)
                return $this->failValidationErrors(lang('RESTful.fieldNotFound', ['Price']));
            $client->price_id = $price->id;
        }
        if (property_exists($inputData, 'legal_status')) {
            $client->legal_status = $inputData->legal_status;
        }
        if (property_exists($inputData, 'blocked')) {
            $client->blocked = $inputData->blocked;
        }
        if (property_exists($inputData, 'company')) {
            $client->company = trim($inputData->company);
        }
        if (property_exists($inputData, 'inn')) {
            $client->inn = trim($inputData->inn);
        }
        if (property_exists($inputData, 'kpp')) {
            $client->kpp = trim($inputData->kpp);
        }
        if (property_exists($inputData, 'okpo')) {
            $client->okpo = trim($inputData->okpo);
        }

        if ($client->hasChanged() && ! $this->clientModel->save($client)) {
            return $this->failValidationErrors($this->clientModel->errors());
        }

        $client->id = $client->id ?: $this->clientModel->getInsertID();

        helper('common');

        // Save client users
        if (property_exists($inputData, 'users') && is_array($inputData->users))
        {
            $clientUsersDb = $this->clientUserModel->where('client_id', $client->id)->findAll();
            $clientUsersToDeactivate = \App\Helpers\ArrayHelper::array_make_keys($clientUsersDb, 'user_id');

            foreach ($inputData->users as $item) {
                $user = model(UserModel::class)->where('guid', $item->user_guid)->first();
                if ($user === null) {
                    return $this->failValidationErrors(lang('RESTful.fieldNotFound', ['User [' . $item->user_guid . ']']));
                }
                $clientUserId = null;
                if (isset($clientUsersToDeactivate[$user->id])) {
                    $clientUserId = $clientUsersToDeactivate[$user->id]->id;
                    unset($clientUsersToDeactivate[$user->id]);
                }
                $clientUser = new ClientUser();
                $clientUser->fill([
                    'id' => $clientUserId,
                    'client_id' => $client->id,
                    'user_id' => $user->id,
                    'main' => $item->main,
                    'active' => 1,
                ]);
                if ($clientUser->hasChanged() && ! $this->clientUserModel->save($clientUser)) {
                    return $this->failValidationErrors($this->clientUserModel->errors());
                }
            }

            if (count($clientUsersToDeactivate)) {
                $this->clientUserModel->whereIn('id', array_column($clientUsersToDeactivate, 'id'))->update(null, ['active' => 0]);
            }
        }

        // Save client addresses
        if (property_exists($inputData, 'addresses') && is_array($inputData->addresses))
        {
            $clientAddressesDb = $this->clientAddressModel->where('client_id', $client->id)->findAll();
            $clientAddressesToDelete = \App\Helpers\ArrayHelper::array_make_keys($clientAddressesDb, 'full_address');

            foreach ($inputData->addresses as $item) {
                $addressId = null;
                if (isset($clientAddressesToDelete[$item->full_address])) {
                    $addressId = $clientAddressesToDelete[$item->full_address]->id;
                    unset($clientAddressesToDelete[$item->full_address]);
                }
                $address = new ClientAddress();
                $address->fill([
                    'id' => $addressId,
                    'client_id' => $client->id,
                    'type' => 'delivery',
                    'address' => $item->full_address,
                    'default' => $item->default,
                    'address_json' => is_object($item->data) ? json_encode($item->data) : null,
                ]);
                if ($address->hasChanged() && ! $this->clientAddressModel->save($address)) {
                    return $this->failValidationErrors($this->clientAddressModel->errors());
                }
            }

            if (count($clientAddressesToDelete)) {
                $this->clientAddressModel->whereIn('id', array_column($clientAddressesToDelete, 'id'))->delete();
            }
        }

        // Transaction commit
        $this->clientModel->db->transCommit();

        return $crud == 'create' ? $this->respondCreated() : $this->respondUpdated();
    }

    /**
     * Create a new or update an existing Client's bank account
     *
     * @param string $clientGuid
     * @param string $bankAccountGuid
     *
     * @return mixed
     */
    public function saveBankAccount(string $clientGuid, string $bankAccountGuid)
    {
        $inputData = $this->request->getJSON();

        if (empty($inputData)) {
            return $this->failValidationErrors(lang('RESTful.invalidInput', ['']));
        }

        $client = $this->clientModel->findByGuid($clientGuid);
        if ($client === null) {
            return $this->failValidationErrors(lang('RESTful.fieldNotFound', ['Client [' . $clientGuid . ']']));
        }

        // Save Client's bank account
        $bankAccount = $this->clientBankAccountModel->findByGuid($bankAccountGuid);
        if ($bankAccount === null) {
            $crud = 'create';
            $bankAccount = new ClientBankAccount();
        } else {
            $crud = 'update';
        }
        $bankAccount->guid = $bankAccountGuid;
        $bankAccount->client_id = $client->id;
        if (property_exists($inputData, 'bank_guid')) {
            $bank = model(BankModel::class)->findByGuid($inputData->bank_guid);
            if ($bank === null) {
                return $this->failValidationErrors(lang('RESTful.fieldNotFound', ['Bank [' . $inputData->bank_guid . ']']));
            }
            $bankAccount->bank_id = $bank->id;
        }
        if (property_exists($inputData, 'checking_account')) {
            $bankAccount->checking_account = trim($inputData->checking_account);
        }
        if (property_exists($inputData, 'active')) {
            $bankAccount->active = $inputData->active;
        }

        if ($bankAccount->hasChanged() && ! $this->clientBankAccountModel->save($bankAccount)) {
            return $this->failValidationErrors($this->clientBankAccountModel->errors());
        }

        return $crud == 'create' ? $this->respondCreated() : $this->respondUpdated();
    }

    /**
     * Create a new or update an existing Client's car
     *
     * @param string $clientGuid
     * @param string $carGuid
     *
     * @return mixed
     */
    public function saveCar(string $clientGuid, string $carGuid)
    {
        $inputData = $this->request->getJSON();

        if (empty($inputData)) {
            return $this->failValidationErrors(lang('RESTful.invalidInput', ['']));
        }

        $client = $this->clientModel->findByGuid($clientGuid);
        if ($client === null) {
            return $this->failValidationErrors(lang('RESTful.fieldNotFound', ['Client [' . $clientGuid . ']']));
        }

        // Save Client's car
        $clientCar = $this->clientCarModel->findByGuid($carGuid);
        if ($clientCar === null) {
            $crud = 'create';
            $clientCar = new ClientCar();
        } else {
            $crud = 'update';
        }
        $clientCar->guid = $carGuid;
        $clientCar->client_id = $client->id;
        if (property_exists($inputData, 'brand_guid')) {
            $featureBrand = model(FeatureModel::class)->findByReference('car_brand');
            if ($featureBrand === null) {
                return $this->failValidationErrors(lang('RESTful.fieldNotFound', ['The feature with reference_type=car_brand']));
            } echo $featureBrand->id;
            $brand = model(FeatureValueModel::class)->findByFeatureGuid($featureBrand->id, $inputData->brand_guid);
            if ($brand === null) {
                return $this->failValidationErrors(lang('RESTful.fieldNotFound', ['Brand [' . $inputData->brand_guid .']']));
            }
            $clientCar->car_brand_id = $brand->id;
        }
        if (property_exists($inputData, 'model_guid')) {
            $model =  model(VehicleModelModel::class)->findByGuid($inputData->model_guid);
            if ($model === null) {
                return $this->failValidationErrors(lang('RESTful.fieldNotFound', ['Model [' . $inputData->model_guid . ']']));
            }
            $clientCar->car_model_id = $model->id;
        }
        if (property_exists($inputData, 'modification_guid')) {
            $modification =  model(VehicleModificationModel::class)->findByGuid($inputData->modification_guid);
            if ($modification === null) {
                return $this->failValidationErrors(lang('RESTful.fieldNotFound', ['Modification [' . $inputData->modification_guid . ']']));
            }
            $clientCar->car_modification_id = $modification->id;
        }
        if (property_exists($inputData, 'year')) {
            $clientCar->year = $inputData->year;
        }
        if (property_exists($inputData, 'vin')) {
            $clientCar->vin = trim($inputData->vin);
        }
        if (property_exists($inputData, 'active')) {
            $clientCar->active = $inputData->active;
        }

        if ($clientCar->hasChanged() && ! $this->clientCarModel->save($clientCar)) {
            return $this->failValidationErrors($this->clientCarModel->errors());
        }

        return $crud == 'create' ? $this->respondCreated() : $this->respondUpdated();
    }

    /**
     * Create a new or update an existing Client's storage contract
     *
     * @param string $clientGuid
     * @param string $storageContractGuid
     *
     * @return mixed
     */
    public function saveStorageContract(string $clientGuid, string $storageContractGuid)
    {
        $inputData = $this->request->getJSON();

        if (empty($inputData)) {
            return $this->failValidationErrors(lang('RESTful.invalidInput', ['']));
        }

        $client = $this->clientModel->findByGuid($clientGuid);
        if ($client === null) {
            return $this->failValidationErrors(lang('RESTful.fieldNotFound', ['Client [' . $clientGuid . ']']));
        }

        // Save Client's storage contract
        $clientStorageContract = $this->clientStorageContractModel->findByGuid($storageContractGuid);
        if ($clientStorageContract === null) {
            $crud = 'create';
            $clientStorageContract = new ClientStorageContract();
        } else {
            $crud = 'update';
        }
        $clientStorageContract->guid = $storageContractGuid;
        $clientStorageContract->client_id = $client->id;
        if (property_exists($inputData, 'client_car_guid')) {
            $clientCar =  $this->clientCarModel->findByGuid($inputData->client_car_guid);
            if ($clientCar === null) {
                return $this->failValidationErrors(lang('RESTful.fieldNotFound', ['Client car [' . $inputData->client_car_guid . ']']));
            }
            $clientStorageContract->client_car_id = $clientCar->id;
        }
        if (property_exists($inputData, 'pickup_in_guid')) {
            $pickup =  model(OldPickupModel::class)->find($inputData->pickup_in_guid);
            if ($pickup === null) {
                return $this->failValidationErrors(lang('RESTful.fieldNotFound', ['Pickup [' . $inputData->pickup_in_guid . ']']));
            }
            $clientStorageContract->pickup_in_id = $pickup['id_pickup'];
        }
        if (property_exists($inputData, 'pickup_out_guid')) {
            $pickup =  model(OldPickupModel::class)->find($inputData->pickup_out_guid);
            if ($pickup === null) {
                return $this->failValidationErrors(lang('RESTful.fieldNotFound', ['Pickup [' . $inputData->pickup_out_guid . ']']));
            }
            $clientStorageContract->pickup_out_id = $pickup['id_pickup'];
        }
        if (property_exists($inputData, 'number')) {
            $clientStorageContract->number = trim($inputData->number);
        }
        if (property_exists($inputData, 'date')) {
            $clientStorageContract->date = trim($inputData->date);
        }
        if (property_exists($inputData, 'amount')) {
            $clientStorageContract->amount = $inputData->amount;
        }
        if (property_exists($inputData, 'status')) {
            $clientStorageContract->status = $inputData->status;
        }
        if (property_exists($inputData, 'storage_period')) {
            $clientStorageContract->storage_period = $inputData->storage_period;
        }
        if (property_exists($inputData, 'pickup_date')) {
            $clientStorageContract->pickup_date = $inputData->pickup_date;
        }
        if (property_exists($inputData, 'pickup_plan_date')) {
            $clientStorageContract->pickup_plan_date = $inputData->pickup_plan_date;
        }
        if (property_exists($inputData, 'pickup_real_date')) {
            $clientStorageContract->pickup_real_date = $inputData->pickup_real_date;
        }
        if (property_exists($inputData, 'products')) {
            $clientStorageContract->products = is_array($inputData->products) && count($inputData->products) ?
                json_encode($inputData->products) : null;
        }

        if ($clientStorageContract->hasChanged() && ! $this->clientStorageContractModel->save($clientStorageContract)) {
            return $this->failValidationErrors($this->clientStorageContractModel->errors());
        }

        return $crud == 'create' ? $this->respondCreated() : $this->respondUpdated();
    }

}
