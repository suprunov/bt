<?php // TODO deleted users, not active in 1s users
// TODO the same blocks to the methods
// TODO email verification - to the new controller
// TODO do we need ShieldLogin extends ?
// TODO move register funcs to the register controller


namespace App\Auth\Controllers;

use App\Entities\User;
use App\Entities\UserContactPerson;
use App\Libraries\DaData\DadataClient;
use App\Models\UserContactPersonModel;
use App\Models\UserModel;
use Bonfire\View\Themeable;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\Database\Exceptions\DataException;
use CodeIgniter\HTTP\Response;
use CodeIgniter\I18n\Time;
use CodeIgniter\Shield\Controllers\LoginController as ShieldLogin;
use CodeIgniter\Shield\Models\UserIdentityModel;
use App\Auth\Authentication\Authenticators\Session;
use CodeIgniter\Shield\Authentication\Authenticators\Session as ShieldSession;

class LoginController extends ShieldLogin // TODO do we need ShieldLogin ?
{
    use Themeable;
    use ResponseTrait;

    protected UserModel $userModel;
    protected UserContactPersonModel $userContactPersonModel;
    protected UserIdentityModel $userIdentityModel;

    public function __construct()
    {
        $this->theme = 'Auth';
        helper(['auth', 'form', 'common', 'setting', 'text']);

        $this->userModel = model(UserModel::class);
        $this->userContactPersonModel = model(UserContactPersonModel::class);
        $this->userIdentityModel = model(UserIdentityModel::class);
    }

    /**
     * Display the login view.
     */
    public function loginView(): \CodeIgniter\HTTP\RedirectResponse|string|null
    {
        if (auth()->loggedIn()) {
            return redirect()->to(current_url());
        }

        return $this->render(config('Auth')->views['pa-login'], [
            'allowRemember' => setting('Auth.sessionConfig')['allowRemembering'],
        ]);
    }

    /**
     * Send a verification code on the phone and create a new user if he does not exist.
     *
     * @return Response
     * @throws \Exception
     */
    public function sendCodeAction(): Response
    {
        $recaptchaResponse = service('request')->getPost('g-recaptcha-response');
        if (!service('recaptcha')->check($recaptchaResponse, service('request')->getServer('REMOTE_ADDR'))) {
            return $this->fail(lang('Form.wrongCaptcha'), 451);
        }

        $phoneNumber = \App\Helpers\PhoneHelper::format('', $this->request->getPost('phone'), ['type' => 'clear']);

        // Validate input data
        if (! $this->validate(['phone' => 'required|valid_phone'])) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        if (auth()->loggedIn()) {
            return $this->fail(lang('Auth.alreadyLoggedIn'), 400);
        }

        $user = $this->userModel->query(['phone' => $phoneNumber, 'published' => 1])->first();

        if ($user === null) {
            // Create user if it does not exist
            $user = new User();
            $user->username = $phoneNumber;
            $user->phone = $phoneNumber;
            // Try saving user
            try {
                if (! $this->userModel->save($user)) {
                    log_message('error', 'User errors', $this->userModel->errors());
                    return $this->fail('Error saving user', 500);
                }
            } catch (DataException $e) {
                log_message('debug', 'SAVING USER: ' . $e->getMessage());
            }
            $user->id = $this->userModel->getInsertID();
        } else {
            // Check user for ban
            if ($user->blocked) {
                return $this->fail(lang('Auth.userIsBlocked'), 400);
            }
            // Check for an unexpired identity that is fresh enough to not resubmit the code.
            $identity = $this->userIdentityModel->getIdentityByType($user, Session::ID_TYPE_PHONE_CODE);
            if ($identity !== null && !Time::now()->isAfter($identity->expires) &&
                !Time::now()->subSeconds(setting('Auth.phoneCodeRepeatTime'))->isAfter($identity->created_at))
            {
                $timeout =  setting('Auth.phoneCodeRepeatTime') + Time::now()->difference($identity->created_at)->getSeconds();
                return $this->respond(['result' => 'success', 'timeout' => $timeout], 200);
            }
        }

        // Delete expired identity
        $this->userIdentityModel->deleteIdentitiesByType($user, Session::ID_TYPE_PHONE_CODE);

        // Generate the code
        if(env("CI_ENVIRONMENT") === "production") {
            $verificationCode = random_string('numeric', 6);
            $smsMessage = "Код подтверждения {$verificationCode} для входа в аккаунт BlackTyres.ru";
            if (!service('sms')->send('+7' . $phoneNumber, $smsMessage)) {
                return $this->fail(lang('Auth.smsNotSent'), 400);
            }
        } else {
            $verificationCode = 666666;
            $smsMessage = "Код подтверждения {$verificationCode} для входа в аккаунт BlackTyres.ru";
            if (0 && !service('sms')->send('+7' . $phoneNumber, $smsMessage)) {
                return $this->fail(lang('Auth.smsNotSent'), 400);
            }
        }

        // Save new identity
        $this->userIdentityModel->insert([ // TODO move to user identity model
            'user_id' => $user->id,
            'type'    => Session::ID_TYPE_PHONE_CODE,
            'secret'  => $user->phone,
            'secret2' => service('passwords')->hash($verificationCode),
            'expires' => Time::now()->addSeconds(setting('Auth.phoneCodeLifetime'))->format('Y-m-d H:i:s'),
        ]);

        return $this->respond(['result' => 'success']);
    }

    /**
     * Check verification code.
     *
     * @return Response
     */
    public function checkCodeAction(): Response
    {
        $phoneNumber      = \App\Helpers\PhoneHelper::format('', $this->request->getPost('phone'), ['type' => 'clear']);
        $verificationCode = $this->request->getPost('code');
        $remember         = (bool)$this->request->getPost('remember');

        // Validate input data
        if (! $this->validate(['phone' => 'required|valid_phone', 'code' => 'required|numeric'])) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        if (auth()->loggedIn()) {
            return $this->fail(lang('Auth.alreadyLoggedIn'), 400);
        }

        $user = $this->userModel->query(['phone' => $phoneNumber, 'published' => 1])->first();

        if ($user === null) {
            return $this->fail(lang('Auth.userNotFound'), 400);
        }

        // Check user for ban
        if ($user->blocked) {
            return $this->fail(lang('Auth.userIsBlocked'), 400);
        }
        // Check if the identity has expired
        $identity = $this->userIdentityModel->getIdentityByType($user, Session::ID_TYPE_PHONE_CODE);
        if ($identity === null || Time::now()->isAfter($identity->expires)) {
            return $this->fail(lang('Auth.codeExpired'), 400);
        }

        // Check credentials
        if (! service('passwords')->verify($verificationCode, $identity->secret2)) {
            return $this->respond(['result' => 'error', 'messages' => [lang('Auth.incorrectCode')]], 400);
        }

        if ($user->active) {
            // Attempt to log in
            auth('session')->remember($remember)->loginById($user->id);
            if (! auth('session')->loggedIn()) {
                return $this->respond(['result' => 'error', 'messages' => [lang('Auth.incorrectCode')]], 400);
            }

            // Transfer all session data to an authorized user
            service('CITransit')->setFlashEvent(100, 'login');

            // Delete credentials
            $this->userIdentityModel->deleteIdentitiesByType($user, Session::ID_TYPE_PHONE_CODE);

            return $this->respond(['result' => 'success']);

        } else {
            // Basic registration required
            return $this->respond(['result' => 'registration_required']);
        }
    }

    /**
     * Register new user and authorize him.
     *
     * @return Response
     */
    public function registerAction(): Response
    {
        $phoneNumber      = \App\Helpers\PhoneHelper::format('', $this->request->getPost('phone'), ['type' => 'clear']);
        $verificationCode = $this->request->getPost('code');
        $remember         = (bool)$this->request->getPost('remember');
        $email            = trim($this->request->getPost('email'));
        $name             = trim($this->request->getPost('name')) ?: null;
        $inn              = $this->request->getPost('inn') ?: null;
        $mailAgreement    = (bool)$this->request->getPost('mailAgree');
        $legalStatus      = $this->request->getPost('legalStatus') ?: null;

        // Validate input data
        $validationRules = [
            'phone'       => 'required|valid_phone',
            'code'        => 'required|numeric',
            'email'       => 'required|valid_email',
            'name'        => 'required_without[inn]',
            'inn'         => 'required_without[name]',
            'legalStatus' => 'required|in_list[fiz,yur]',
        ];
        if (! $this->validate($validationRules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }
        if ($legalStatus === 'yur' && ! $this->validate(['inn' => 'required'])) {
            return $this->failValidationErrors($this->validator->getErrors());
        } elseif ($legalStatus === 'fiz' && ! $this->validate(['name' => 'required'])) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        if (auth()->loggedIn()) {
            return $this->fail(lang('Auth.alreadyLoggedIn'), 400);
        }

        $user = $this->userModel->query(['phone' => $phoneNumber, 'published' => 1])->first();

        if ($user === null) {
            return $this->fail(lang('Auth.userNotFound'), 400);
        }

        // Check user for ban
        if ($user->blocked) {
            return $this->fail(lang('Auth.userIsBlocked'), 400);
        }

        // Check if the identity has expired, but give it extra time (1 hour) for registration
        $identity = $this->userIdentityModel->getIdentityByType($user, Session::ID_TYPE_PHONE_CODE);
        if ($identity === null || Time::now()->subMinutes(60)->isAfter($identity->expires)) {
            return $this->fail(lang('Auth.codeExpired'), 400);
        }

        // Check credentials
        if (! service('passwords')->verify($verificationCode, $identity->secret2)) {
            return $this->respond(['result' => 'error', 'messages' => [lang('Auth.incorrectCode')]], 400);
        }

        // Check if the email is already taken by another account
        $usersWithTheSameEmail = $this->userModel->query(['email' => $email, 'emailVerified' => 1])->first();
        if ($usersWithTheSameEmail !== null) {
            return $this->respond(['result' => 'error', 'messages' => [lang('Auth.emailIsAlreadyTaken')]], 400);
        }

        // Check if the INN is already taken by another account
        if ($legalStatus === 'yur') {
            $usersWithTheSameINN = $this->userModel->query(['inn' => trim($inn)])->first();
            if ($usersWithTheSameINN !== null) {
                return $this->respond(['result' => 'error', 'messages' => [lang('Auth.INNIsAlreadyTaken')]], 400);
            }
        }

        // Transaction start
        $this->userModel->db->transStart();

        // Save user
        $user->active = true;
        $user->email_main = trim($email);
        $user->phone_notify = $mailAgreement;
        $user->email_notify = $mailAgreement;
        $user->location_id  = session('geoip')['id'];
        $user->legal_status = $legalStatus;
        if ($user->legal_status === 'yur') {
            if ($companyData = (new DadataClient())->getCompany(trim($inn))) {
                $user->name = $companyData->name;
                $user->legal_data = $companyData->toJSON();
            }
            $user->inn = trim($inn);
            $user->legal_status = 'yur';
        } else {
            $user->name = trim($name);
            $user->first_name = trim($name);
            $user->legal_status = 'fiz';
        }
        try {
            if (! $this->userModel->save($user)) {
                log_message('error', 'User errors', $this->userModel->errors());
                return $this->fail('Error saving user', 500);
            }
        } catch (DataException $e) {
            log_message('debug', 'SAVING USER: ' . $e->getMessage());
        }

        // Save default user contact person
        if ($user->legal_status === 'fiz') {
            $userContactPerson = new UserContactPerson();
            $userContactPerson->user_id = $user->id;
            $userContactPerson->first_name = $user->first_name;
            $userContactPerson->phone = $user->phone;
            try {
                if (! $this->userContactPersonModel->save($userContactPerson)) {
                    log_message('error', 'User errors', $this->userContactPersonModel->errors());
                    return $this->fail('Error saving user contact person', 500);
                }
            } catch (DataException $e) {
                log_message('debug', 'SAVING USER CONTACT PERSON: ' . $e->getMessage());
            }
        }

        // Transaction commit
        $this->userModel->db->transCommit();

        // Import user for 1C
        $this->userModel->importUserToXML($user->id);

        // Attempt to log in
        auth('session')->remember($remember)->loginById($user->id);
        if (! auth('session')->loggedIn()) {
            return $this->respond(['result' => 'error', 'messages' => [lang('Auth.incorrectCode')]], 400);
        }

        // Delete credentials
        $this->userIdentityModel->deleteIdentitiesByType($user, Session::ID_TYPE_PHONE_CODE);

        // Transfer all session data to an authorized user
        service('CITransit')->setFlashEvent(100, 'login');

        // Send confirmation email
        $this->sendVerificationEmail($user);

        return $this->respond(['result' => 'success']);
    }


    private function sendTokenEmail(User $user): bool
    {
        // Delete any previous magic-link identities
        $this->userIdentityModel->deleteIdentitiesByType($user, ShieldSession::ID_TYPE_MAGIC_LINK);

        // Generate the code and save it as an identity
        helper('text');
        $token = random_string('crypto', 20);

        $this->userIdentityModel->insert([
            'user_id' => $user->id,
            'type'    => ShieldSession::ID_TYPE_MAGIC_LINK,
            'secret'  => $token,
            'expires' => Time::now()->addSeconds(setting('Auth.magicLinkLifetime'))->format('Y-m-d H:i:s'),
        ]);

        // Send the user an email with the code
        $email = \Config\Services::email();
        $email->setFrom(setting('Email.fromEmail'), setting('Email.fromName') ?? '');
        $email->setTo($user->email_main);
        $email->setSubject(lang('Auth.magicLinkSubject'));
        $email->setMessage(view(setting('Auth.views')['magic-link-email'], ['token' => $token]));

        if ($email->send(false) === false) {
            log_message('error', $email->printDebugger(['headers']));
            return false;
        }
        // Clear the email
        $email->clear();

        return true;
    }

    /**
     * @throws \ReflectionException
     */
    private function sendVerificationEmail(User $user): bool
    {
        $token = $this->createVerifyEmailIdentity($user);

        $link  = rtrim(setting('App.baseURL'), '/') .
            route_to('verify-email-check', $user->id, $token);

        // Send the email
        $email = \Config\Services::email();
        $email->setFrom(setting('Email.fromEmail'), setting('Email.fromName'));
        $email->setTo($user->email_main);
        $email->setSubject('Подтвердите электронную почту');
        $email->setMessage(view(
            setting('Auth.views')['email-verify-email'],
            ['link' => $link, 'name' => auth()->user()->first_name ?: auth()->user()->name]
        ));

        if ($email->send(false) === false) {
            //throw new RuntimeException('Cannot send email for user: ' . $user->email . "\n" . $email->printDebugger(['headers']));
            return false;
        }
        // Clear the email
        $email->clear();

        return true;
    }

    public function verifyEmailView()
    {
        if (!auth()->loggedIn()) {
            return redirect()->to(current_url());
        }

        return $this->render(config('Auth')->views['verify-email'], [
            'email' => auth()->user()->email_main,
        ]);
    }

    /**
     * @throws \ReflectionException
     */
    public function verifyEmailSend()
    {
        $email = $this->request->getPost('email');
        $user  = auth()->user();

        if (!auth()->loggedIn()) {
            return $this->respond(['result' => 'error', 'messages' => [lang('Auth.unauthorized')]], 400);
        }

        if ($user->email_verified) {
            // Check if there is a change email identity
            $changeEmailIdentity = $this->userIdentityModel->getIdentityByType($user, Session::ID_TYPE_PHONE_CODE_CHANGE_EMAIL);
            if ($changeEmailIdentity === null) {
                return $this->respond(['result' => 'error', 'messages' => [lang('Auth.emailAlreadyVerified')]], 400);
            } elseif (Time::now()->isAfter($changeEmailIdentity->expires)) {
                return $this->fail(lang('Auth.changePhoneCodeExpired'), 400);
            }
        }

        // Check if the email is already taken by another account
        $userWithTheSameEmail = $this->userModel->query(['email' => $email, 'emailVerified' => 1])->first();
        if ($userWithTheSameEmail !== null) {
            return $this->respond(['result' => 'error', 'messages' => [lang('Auth.emailIsAlreadyTaken')]], 400);
        }

        if ($user->email_main !== $email) {
            // Delete previous identities
            $this->userIdentityModel->deleteIdentitiesByType($user, Session::ID_TYPE_EMAIL_VERIFY);
            // Update user email
            $user->email_main = $email;
            $user->email_verified = 0;
            try {
                if (!$this->userModel->save($user)) {
                    log_message('error', 'User errors', $this->userModel->errors());
                    return $this->fail('Error saving user', 500);
                }
            } catch (DataException $e) {
                log_message('debug', 'SAVING USER: ' . $e->getMessage());
            }
        }

        // Import user for 1C
        $this->userModel->importUserToXML($user->id);

        // Delete previous identities for the second step (when it is an email change request)
        $this->userIdentityModel->deleteIdentitiesByType($user, Session::ID_TYPE_PHONE_CODE_CHANGE_EMAIL);

        if ($this->sendVerificationEmail($user)) {
            return $this->respond(['result' => 'success', 'messages' => [lang('Auth.emailSentToYou')]], 200);
        } else {
            return $this->respond(['result' => 'error', 'messages' => [lang('Auth.emailSentError')]], 200);
        }
    }

    public function verifyEmail(int $userId, string $token)
    {
        $user = $this->userModel->find($userId);
        if ($user === null) {
            return $this->fail('Invalid request', 400);
        }

        // Check if the email is already taken by another account
        $usersWithTheSameEmail = $this->userModel->query(['email' => $user->email_main, 'emailVerified' => 1])->first();

        if ($user->email_verified) {
            $message = lang('Auth.emailAlreadyVerified');
        } elseif ($usersWithTheSameEmail !== null) {
            $message = lang('Auth.emailIsAlreadyTaken');
        } else{
            $identities = $this->userIdentityModel->getIdentitiesByTypes($user, [Session::ID_TYPE_EMAIL_VERIFY]);
            $message  = lang('Auth.incorrectLink');
            foreach ($identities as $identity) {
                if (Time::now()->isAfter($identity->expires))
                    continue;
                if (hash('sha256', $token) === $identity->secret2 && $user->email_main === $identity->name) {
                    $message = lang('Auth.emailVerifySuccess', [$user->email_main]);
                    // Save the information that the email is verified
                    $user->email_verified = 1;
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
                    break;
                }
            }
        }

        $this->userIdentityModel->deleteIdentitiesByType($user, Session::ID_TYPE_EMAIL_VERIFY);

        session()->setFlashdata('email-verify-message', $message);
        return redirect()->to('kabinet/verify-email');
    }

    /**
     * @throws \ReflectionException
     * @throws \Exception
     */
    private function createVerifyEmailIdentity(User $user): string
    {
        // Delete previous expired identities
        $previousIdentities = $this->userIdentityModel->getIdentitiesByTypes($user, [Session::ID_TYPE_EMAIL_VERIFY]);
        foreach ($previousIdentities as $identity) {
            if (Time::now()->isAfter($identity->expires)) {
                $this->userIdentityModel->delete($identity->id);
            }
        }

        $this->userIdentityModel->insert([
            'type'    => Session::ID_TYPE_EMAIL_VERIFY,
            'user_id' => $user->id,
            'name'    => $user->email_main,
            'secret'  => random_string('crypto', 64), // any random string - just for uniqueness
            'secret2' => hash('sha256', $rawToken = random_string('crypto', 64)),
            'expires' => Time::now()->addSeconds(setting('Auth.emailVerifyLifetime'))->format('Y-m-d H:i:s'),
        ]);

        return $rawToken;
    }

}
