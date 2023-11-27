<?php

namespace App\Controllers\Api\v1;

use App\Controllers\ApiController;
use App\Entities\User;
use App\Models\UserModel;

class UserController extends ApiController
{
    protected UserModel $userModel;

    public function __construct()
    {
        $this->userModel = model(UserModel::class);
    }

    /**
     * Create a new or update an existing User.
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

        // Save User
        $user = $this->userModel->where('guid', $guid)->first();
        if ($user === null) {
            $crud = 'create';
            $user = new User();
        } else {
            $crud = 'update';
        }
        $user->guid = $guid;
        if (property_exists($inputData, 'fullname')) {
            $user->full_name = trim($inputData->fullname);
        }
        if (property_exists($inputData, 'lastname')) {
            $user->last_name = trim($inputData->lastname);
        }
        if (property_exists($inputData, 'firstname')) {
            $user->first_name = trim($inputData->firstname);
        }
        if (property_exists($inputData, 'middlename')) {
            $user->middle_name = trim($inputData->middlename);
        }
        if (property_exists($inputData, 'birth_date')) {
            $user->birth_date = trim($inputData->birth_date);
        }
        if (property_exists($inputData, 'gender')) {
            $user->gender = trim($inputData->gender);
        }
        if (property_exists($inputData, 'passport')) {
            $user->passport = trim($inputData->passport);
        }
        if (property_exists($inputData, 'phone')) {
            $user->phone = trim($inputData->phone);
        }
        if (property_exists($inputData, 'phone_notify')) {
            $user->phone_notify = trim($inputData->phone_notify);
        }
        if (property_exists($inputData, 'email')) {
            $user->email = trim($inputData->email);
        }
        if (property_exists($inputData, 'email_confirmed')) {
            $user->email_confirmed = trim($inputData->email_confirmed);
        }
        if (property_exists($inputData, 'email_notify')) {
            $user->email_notify = trim($inputData->email_notify);
        }
        if (property_exists($inputData, 'active')) {
            $user->active = $inputData->active;
        }

        if ($user->hasChanged() && ! $this->userModel->save($user)) {
            return $this->failValidationErrors($this->userModel->errors());
        }

        return $crud == 'create' ? $this->respondCreated() : $this->respondUpdated();
    }
}
