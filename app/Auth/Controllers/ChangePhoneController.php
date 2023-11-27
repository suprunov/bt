<?php

namespace App\Auth\Controllers;

use App\Models\UserModel;
use Bonfire\View\Themeable;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\Database\Exceptions\DataException;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\Response;
use CodeIgniter\I18n\Time;
use CodeIgniter\Shield\Controllers\LoginController as ShieldLogin;
use CodeIgniter\Shield\Models\UserIdentityModel;
use App\Auth\Authentication\Authenticators\Session;

class ChangePhoneController extends ShieldLogin // TODO do we need ShieldLogin ?
{
    use Themeable;
    use ResponseTrait;

    protected UserModel $userModel;
    protected UserIdentityModel $userIdentityModel;

    public function __construct()
    {
        $this->theme = 'Auth';
        helper(['auth', 'form', 'common', 'setting', 'text']);

        $this->userModel = model(UserModel::class);
        $this->userIdentityModel = model(UserIdentityModel::class);
    }

    /**
     * Display a view to change phone.
     */
    public function changePhoneView(): string|RedirectResponse|null
    {
        if (!auth()->loggedIn()) {
            return redirect()->to(current_url());
        }

        return $this->render(config('Auth')->views['pa-change-phone']);
    }

    /**
     * Send a verification code to confirm ownership of the number.
     *
     * @return Response
     * @throws \Exception
     */
    public function sendCodeAction(): Response
    {
        if (!auth()->loggedIn()) {
            return $this->fail(lang('Auth.unauthorized'), 400);
        }

        $user = auth()->user();

        // Check user for ban
        if ($user->blocked) {
            return $this->fail(lang('Auth.userIsBlocked'), 400);
        }
        // Check for an unexpired identity that is fresh enough to not resubmit the code.
        $identity = $this->userIdentityModel->getIdentityByType($user, Session::ID_TYPE_PHONE_CODE_CHANGE);
        if ($identity !== null && !Time::now()->isAfter($identity->expires) &&
            !Time::now()->subSeconds(setting('Auth.phoneCodeRepeatTime'))->isAfter($identity->created_at))
        {
            $timeout =  setting('Auth.phoneCodeRepeatTime') + Time::now()->difference($identity->created_at)->getSeconds();
            return $this->respond(['result' => 'success', 'timeout' => $timeout], 200);
        }

        // Delete expired identity
        $this->userIdentityModel->deleteIdentitiesByType($user, Session::ID_TYPE_PHONE_CODE_CHANGE);

        // Generate the code
        $verificationCode = random_string('numeric', 6);
        //$verificationCode = 666666; // TODO
        $smsMessage = "Код подтверждения {$verificationCode} для изменения данных на BlackTyres.ru";
        if (/*0 &&*/ ! service('sms')->send('+7' . $user->phone, $smsMessage)) { // TODO
            return $this->fail(lang('Auth.smsNotSent'), 400);
        }

        // Save new identity
        $this->userIdentityModel->insert([ // TODO move to user identity model
            'user_id' => $user->id,
            'type'    => Session::ID_TYPE_PHONE_CODE_CHANGE,
            'secret'  => $user->phone,
            'secret2' => service('passwords')->hash($verificationCode),
            'expires' => Time::now()->addSeconds(setting('Auth.phoneCodeLifetime'))->format('Y-m-d H:i:s'),
        ]);

        return $this->respond(['result' => 'success']);
    }

    /**
     * Check the verification code to confirm ownership of the number.
     *
     * @return Response
     */
    public function checkCodeAction(string $action): Response
    {
        $verificationCode = $this->request->getPost('code');

        // Validate input data
        if (! $this->validate(['code' => 'required|numeric'])) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        if (!auth()->loggedIn()) {
            return $this->fail(lang('Auth.unauthorized'), 400);
        }

        $user = auth()->user();

        // Check user for ban
        if ($user->blocked) {
            return $this->fail(lang('Auth.userIsBlocked'), 400);
        }
        // Check if the identity has expired
        $identity = $this->userIdentityModel->getIdentityByType($user, Session::ID_TYPE_PHONE_CODE_CHANGE);
        if ($identity === null || Time::now()->isAfter($identity->expires)) {
            return $this->fail(lang('Auth.codeExpired'), 400);
        }

        // Check credentials
        if (! service('passwords')->verify($verificationCode, $identity->secret2)) {
            return $this->respond(['result' => 'error', 'messages' => [lang('Auth.incorrectCode')]], 400);
        }

        switch ($action) {
            case 'phone': // change phone
                $identityType = Session::ID_TYPE_PHONE_CODE_CHANGE_PHONE;
                $secret = 'phone';
                break;
            case 'email': // change email
                $identityType = Session::ID_TYPE_PHONE_CODE_CHANGE_EMAIL;
                $secret = 'email_main';
                break;
        }

        $this->userIdentityModel->db->transBegin();

        // Delete credentials
        $this->userIdentityModel->deleteIdentitiesByType($user, Session::ID_TYPE_PHONE_CODE_CHANGE);
        $this->userIdentityModel->deleteIdentitiesByType($user, $identityType);

        // Save new identity
        $this->userIdentityModel->insert([ // TODO move to user identity model ?
            'user_id' => $user->id,
            'type'    => $identityType,
            'secret'  => $user->{$secret},
            'secret2' => null,
            'expires' => Time::now()->addSeconds(setting('Auth.phoneCodeLifetime'))->format('Y-m-d H:i:s'), // TODO it's ok time period ?
        ]);

        $this->userIdentityModel->db->transCommit();

        return $this->respond(['result' => 'success', 'step'=> 1]);
    }

    /**
     * Send a verification code to change the phone number.
     *
     * @return Response
     * @throws \Exception
     */
    public function sendCode2ndStepAction(): Response
    {
        $phoneNumber = \App\Helpers\PhoneHelper::format('', $this->request->getPost('phone'), ['type' => 'clear']);

        // Validate input data
        if (! $this->validate(['phone' => 'required|valid_phone'])) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        if (!auth()->loggedIn()) {
            return $this->fail(lang('Auth.unauthorized'), 400);
        }

        $user = auth()->user();

        // Check user for ban
        if ($user->blocked) {
            return $this->fail(lang('Auth.userIsBlocked'), 400);
        }

        // Check the second step identity.
        $identity = $this->userIdentityModel->getIdentityByType($user, Session::ID_TYPE_PHONE_CODE_CHANGE_PHONE);
        if ($identity === null || Time::now()->isAfter($identity->expires)) {
            // Delete expired identity
            $this->userIdentityModel->deleteIdentitiesByType($user, Session::ID_TYPE_PHONE_CODE_CHANGE_PHONE);

            return $this->respond(lang('Auth.changePhoneCodeExpired'), 400); // TODO may be redirect ?
        }

        // Check if the identity is fresh enough to not resubmit the code.
        if ($identity->secret2 && !Time::now()->subSeconds(setting('Auth.phoneCodeRepeatTime'))->isAfter($identity->updated_at)) {
            $timeout =  setting('Auth.phoneCodeRepeatTime') + Time::now()->difference($identity->updated_at)->getSeconds();
            return $this->respond(['result' => 'success', 'timeout' => $timeout], 200);
        }

        // Check if the new phone number is available
        $userWithNewNumber = $this->userModel->query(['phone' => $phoneNumber])->first();
        if ($userWithNewNumber !== null) {
            return $this->fail(lang('Auth.phoneIsAlreadyInUse'), 400);
        }

        // Generate the code
        $verificationCode = random_string('numeric', 6);
        //$verificationCode = 666666; // TODO
        $smsMessage = "Код подтверждения {$verificationCode} для изменения данных на BlackTyres.ru";
        if (/*0 &&*/ ! service('sms')->send('+7' . $phoneNumber, $smsMessage)) { // TODO
            return $this->fail(lang('Auth.smsNotSent'), 400);
        }

        // Update the second step identity
        $this->userIdentityModel->update($identity->id, [
            'name'    => $phoneNumber,
            'secret2' => service('passwords')->hash($verificationCode),
            'expires' => Time::now()->addSeconds(setting('Auth.phoneCodeLifetime'))->format('Y-m-d H:i:s'),
        ]);

        return $this->respond(['result' => 'success']);
    }

    /**
     * Check the verification code to change the phone number.
     *
     * @return Response
     */
    public function checkCode2ndStepAction(): Response
    {
        $verificationCode = $this->request->getPost('code');

        // Validate input data
        if (! $this->validate(['code' => 'required|numeric'])) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        if (!auth()->loggedIn()) {
            return $this->fail(lang('Auth.unauthorized'), 400);
        }

        $user = auth()->user();

        // Check user for ban
        if ($user->blocked) {
            return $this->fail(lang('Auth.userIsBlocked'), 400);
        }
        // Check if the identity has expired
        $identity = $this->userIdentityModel->getIdentityByType($user, Session::ID_TYPE_PHONE_CODE_CHANGE_PHONE);
        if ($identity === null || Time::now()->isAfter($identity->expires)) {
            return $this->fail(lang('Auth.codeExpired'), 400);
        }

        // Check credentials
        if (! service('passwords')->verify($verificationCode, $identity->secret2)) {
            return $this->respond(['result' => 'error', 'messages' => [lang('Auth.incorrectCode')]], 400);
        }

        // Update user's phone
        $user->phone = $identity->name;
        $user->username = $identity->name;
        try {
            if (! $this->userModel->save($user)) {
                log_message('error', 'User errors', $this->userModel->errors());
                return $this->fail('Error saving user', 500);
            }
        } catch (DataException $e) {
            log_message('debug', 'SAVING USER: ' . $e->getMessage());
        }

        // Import user for 1C
        $this->userModel->importUserToXML($user->id);

        // Delete credentials
        $this->userIdentityModel->deleteIdentitiesByType($user, Session::ID_TYPE_PHONE_CODE_CHANGE_PHONE);

        return $this->respond(['result' => 'success', 'step' => 2]);
    }
}
