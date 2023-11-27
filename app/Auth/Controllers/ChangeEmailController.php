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

class ChangeEmailController extends ShieldLogin // TODO do we need ShieldLogin ?
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
     * Display a view to change email.
     */
    public function changeEmailView(): string|RedirectResponse|null
    {
        if (!auth()->loggedIn()) {
            return redirect()->to(current_url());
        }

        return $this->render(config('Auth')->views['pa-change-email']);
    }

}
