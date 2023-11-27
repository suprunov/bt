<?php

namespace App\Auth\Controllers;

use App\Auth\Authentication\Authenticators\Session;
use App\Controllers\BaseController;
use App\Models\UserModel;
use Bonfire\View\Themeable;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\Database\Exceptions\DataException;
use CodeIgniter\HTTP\Response;
use CodeIgniter\I18n\Time;
use CodeIgniter\Shield\Models\UserIdentityModel;

class RegisterController extends BaseController
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
    public function deleteAccountView(): \CodeIgniter\HTTP\RedirectResponse|string|null
    {
        if (!auth()->loggedIn()) {
            return redirect()->to(current_url());
        }

        return $this->render(config('Auth')->views['pa-delete-account']);
    }

    public function deleteAccountAction(): Response
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

        $this->userModel->db->transBegin();

        // Delete credentials
        $this->userIdentityModel->deleteIdentitiesByType($user, Session::ID_TYPE_PHONE_CODE_CHANGE);
        // Delete the user
        $this->userModel->delete($user->id);
        // Export user to 1C
        $this->userModel->importUserToXML($user->id);

        $this->userModel->db->transCommit();

        return $this->respond(['result' => 'success']);
    }
}
