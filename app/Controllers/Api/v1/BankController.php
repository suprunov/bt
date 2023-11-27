<?php

namespace App\Controllers\Api\v1;

use App\Controllers\ApiController;
use App\Entities\Bank;
use App\Models\BankModel;

class BankController extends ApiController
{
    protected BankModel $bankModel;

    public function __construct()
    {
        $this->bankModel = model(BankModel::class);
    }

    /**
     * Create a new or update an existing Bank.
     *
     * @param string $guid
     *
     * @return mixed
     */
    public function save(string $guid)
    {
        $inputData = $this->request->getJSON();

        if (empty($inputData)) {
            return $this->failValidationErrors(lang('RESTful.invalidInput', ['']));
        }

        // Save Bank
        $bank = $this->bankModel->findByGuid($guid);
        if ($bank === null) {
            $crud = 'create';
            $bank = new Bank();
        } else {
            $crud = 'update';
        }
        $bank->guid = $guid;
        if (property_exists($inputData, 'name')) {
            $bank->name = trim($inputData->name);
        }
        if (property_exists($inputData, 'bic')) {
            $bank->bic = trim($inputData->bic);
        }
        if (property_exists($inputData, 'corresponding_account')) {
            $bank->corresponding_account = trim($inputData->corresponding_account);
        }
        if (property_exists($inputData, 'active')) {
            $bank->active = $inputData->active;
        }

        if ($bank->hasChanged() && ! $this->bankModel->save($bank)) {
            return $this->failValidationErrors($this->bankModel->errors());
        }
        
        return $crud == 'create' ? $this->respondCreated() : $this->respondUpdated();
    }
}
