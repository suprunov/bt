<?php

namespace Config\Auth;

use CodeIgniter\Shield\Authentication\Authenticators\AccessTokens;
use App\Auth\Authentication\Authenticators\Session;
//use CodeIgniter\Shield\Authentication\Authenticators\Session;
use CodeIgniter\Shield\Config\Auth as ShieldAuth;

class Auth extends ShieldAuth
{
    /**
     * ////////////////////////////////////////////////////////////////////
     * AUTHENTICATION
     * ////////////////////////////////////////////////////////////////////
     */
    public array $views = [
        /* bonfire */
        'layout'                      => 'master',
        'email_layout'                => '\Bonfire\Views\email.php',
        'login'                       => '\Bonfire\Views\Auth\login',
        'register'                    => '\Bonfire\Views\Auth\register',
        'forgotPassword'              => '\CodeIgniter\Shield\Views\forgot_password',
        'resetPassword'               => '\CodeIgniter\Shield\Views\reset_password',
        'action_email_2fa'            => '\CodeIgniter\Shield\Views\email_2fa_show',
        'action_email_2fa_verify'     => '\CodeIgniter\Shield\Views\email_2fa_verify',
        'action_email_2fa_email'      => '\CodeIgniter\Shield\Views\Email\email_2fa_email',
        'action_email_activate_email' => '\CodeIgniter\Shield\Views\Email\email_activate_email',
        'action_email_activate_show'  => '\CodeIgniter\Shield\Views\email_activate_show',
        'magic-link-login'            => '\Bonfire\Views\Auth\magic_link_form',
        'magic-link-message'          => '\Bonfire\Views\Auth\magic_link_message',
        'magic-link-email'            => '\Bonfire\Views\Auth\magic_link_email',
        /* login */
        'pa-login'                    => '\App\Auth\Views\login',
        'pa-check-code'               => '\App\Auth\Views\_check_code',
        'pa-approve-change'           => '\App\Auth\Views\_approve_change',
        'pa-change-phone'             => '\App\Auth\Views\change_phone',
        'pa-change-email'             => '\App\Auth\Views\change_email',
        'pa-delete-account'           => '\App\Auth\Views\delete_account',
        /* verify email */
        'verify-email'                => '\App\Auth\Views\verify_email',
        'verify-email-thanks'         => '\App\Auth\Views\verify_email_thanks',
        'email-verify-email'          => '\App\Auth\Views\Emails\verify_email',
    ];

    /**
     * --------------------------------------------------------------------
     * Redirect urLs
     * --------------------------------------------------------------------
     * The default URL that a user will be redirected to after
     * various auth actions. If you need more flexibility you
     * should extend the appropriate controller and overrider the
     * `getRedirect()` methods to apply any logic you may need.
     */
    public array $redirects = [
        'register' => '/',
        'login'    => '/kabinet/profil/',
        'logout'   => '/',
    ];

    /**
     * --------------------------------------------------------------------
     * Authentication Actions
     * --------------------------------------------------------------------
     * Specifies the class that represents an action to take after
     * the user logs in or registers a new account at the site.
     *
     * Available actions with Shield:
     * - login:    Shield\Authentication\Actions\Email2FA
     * - register: Shield\Authentication\Actions\EmailActivate
     */
    public array $actions = [
        'login'    => null,
        'register' => null,
    ];

    /**
     * --------------------------------------------------------------------
     * Authenticators
     * --------------------------------------------------------------------
     * The available authentication systems, listed
     * with alias and class name. These can be referenced
     * by alias in the auth helper:
     *      auth('api')->attempt($credentials);
     */
    public array $authenticators = [
        'tokens'     => AccessTokens::class,
        'session'    => Session::class,
    ];

    /**
     * --------------------------------------------------------------------
     * Name of Authenticator Header
     * --------------------------------------------------------------------
     * The name of Header that the Authorization token should be found.
     * According to the specs, this should be `Authorization`, but rare
     * circumstances might need a different header.
     */
    public array $authenticatorHeader = [
        'tokens' => 'Authorization',
    ];

    /**
     * --------------------------------------------------------------------
     * Unused Token Lifetime
     * --------------------------------------------------------------------
     * Determines the amount of time, in seconds, that an unused
     * access token can be used.
     */
    public int $unusedTokenLifetime = YEAR;

    /**
     * --------------------------------------------------------------------
     * Default Authenticator
     * --------------------------------------------------------------------
     * The authentication handler to use when none is specified.
     * Uses the $key from the $authenticators array above.
     */
    public string $defaultAuthenticator = 'session';

    /**
     * --------------------------------------------------------------------
     * Authentication Chain
     * --------------------------------------------------------------------
     * The authentication handlers to test logged in status against
     * when using the 'chain' filter. Each handler listed will be checked.
     * If no match is found, then the next in the chain will be checked.
     */
    public array $authenticationChain = [
        'session',
        'tokens',
    ];

    /**
     * --------------------------------------------------------------------
     * Record Last Active Date
     * --------------------------------------------------------------------
     * If true, will always update the `last_active` datetime for the
     * logged in user on every page request.
     */
    public bool $recordActiveDate = true;

    /**
     * --------------------------------------------------------------------
     * Allow Registration
     * --------------------------------------------------------------------
     * Determines whether users can register for the site.
     */
    public bool $allowRegistration = true;

    /**
     * --------------------------------------------------------------------
     * Allow Magic Link Logins
     * --------------------------------------------------------------------
     * If true, will allow the use of "magic links" sent via the email
     * as a way to log a user in without the need for a password.
     * By default, this is used in place of a password reset flow, but
     * could be modified as the only method of login once an account
     * has been set up.
     */
    public bool $allowMagicLinkLogins = true;

    /**
     * --------------------------------------------------------------------
     * Magic Link Lifetime
     * --------------------------------------------------------------------
     * Specifies the amount of time, in seconds, that a magic link is valid.
     */
    public int $magicLinkLifetime = 1 * HOUR;

    /**
     * --------------------------------------------------------------------
     * Session Handler Configuration
     * --------------------------------------------------------------------
     * These settings only apply if you are using the Session Handler
     * for authentication.
     *
     * - field                  The name of the key the logged in user is stored in session
     * - allowRemembering       Does the system allow use of "remember-me"
     * - rememberCookieName     The name of the cookie to use for "remember-me"
     * - rememberLength         The length of time, in seconds, to remember a user.
     */
    public array $sessionConfig = [
        'field'              => 'logged_in',
        'allowRemembering'   => true,
        'rememberCookieName' => 'remember',
        'rememberLength'     => 30 * DAY,
    ];

    /**
     * --------------------------------------------------------------------
     * Minimum Password Length
     * --------------------------------------------------------------------
     * The minimum length that a password must be to be accepted.
     * Recommended minimum value by NIST = 8 characters.
     */
    public int $minimumPasswordLength = 8;

    /**
     * --------------------------------------------------------------------
     * Password Check Helpers
     * --------------------------------------------------------------------
     * The PasswordValidator class runs the password through all of these
     * classes, each getting the opportunity to pass/fail the password.
     * You can add custom classes as long as they adhere to the
     * Password\ValidatorInterface.
     *
     * @var class-string[]
     * @phpstan-ignore-next-line
     */
    public array $passwordValidators = [
        'CodeIgniter\Shield\Authentication\Passwords\CompositionValidator',
        'CodeIgniter\Shield\Authentication\Passwords\NothingPersonalValidator',
        'CodeIgniter\Shield\Authentication\Passwords\DictionaryValidator',
        //'CodeIgniter\Shield\Authentication\Passwords\PwnedValidator',
    ];

    /**
     * --------------------------------------------------------------------
     * Valid login fields
     * --------------------------------------------------------------------
     * Fields that are available to be used as credentials for login.
     */
    public array $validFields = [
        /*'email',*/
        /*'username',*/
        'phone',
    ];

    /**
     * --------------------------------------------------------------------
     * Additional Fields for "Nothing Personal"
     * --------------------------------------------------------------------
     * The NothingPersonalValidator prevents personal information from
     * being used in passwords. The email and username fields are always
     * considered by the validator. Do not enter those field names here.
     *
     * An extended User Entity might include other personal info such as
     * first and/or last names. $personalFields is where you can add
     * fields to be considered as "personal" by the NothingPersonalValidator.
     * For example:
     *     $personalFields = ['firstname', 'lastname'];
     */
    public array $personalFields = [
        'first_name', 'last_name', 'middle_name', 'name', 'birth_date', 'gender', 'passport', 'inn', 'legal_data', 'legal_status'
    ];

    /**
     * --------------------------------------------------------------------
     * Password / Username Similarity
     * --------------------------------------------------------------------
     * Among other things, the NothingPersonalValidator checks the
     * amount of sameness between the password and username.
     * Passwords that are too much like the username are invalid.
     *
     * The value set for $maxSimilarity represents the maximum percentage
     * of similarity at which the password will be accepted. In other words, any
     * calculated similarity equal to, or greater than $maxSimilarity
     * is rejected.
     *
     * The accepted range is 0-100, with 0 (zero) meaning don't check similarity.
     * Using values at either extreme of the *working range* (1-100) is
     * not advised. The low end is too restrictive and the high end is too permissive.
     * The suggested value for $maxSimilarity is 50.
     *
     * You may be thinking that a value of 100 should have the effect of accepting
     * everything like a value of 0 does. That's logical and probably true,
     * but is unproven and untested. Besides, 0 skips the work involved
     * making the calculation unlike when using 100.
     *
     * The (admittedly limited) testing that's been done suggests a useful working range
     * of 50 to 60. You can set it lower than 50, but site users will probably start
     * to complain about the large number of proposed passwords getting rejected.
     * At around 60 or more it starts to see pairs like 'captain joe' and 'joe*captain' as
     * perfectly acceptable which clearly they are not.
     *
     * To disable similarity checking set the value to 0.
     *     public $maxSimilarity = 0;
     */
    public int $maxSimilarity = 50;

    /**
     * --------------------------------------------------------------------
     * Encryption Algorithm to use
     * --------------------------------------------------------------------
     * Valid values are
     * - PASSWORD_DEFAULT (default)
     * - PASSWORD_BCRYPT
     * - PASSWORD_ARGON2I  - As of PHP 7.2 only if compiled with support for it
     * - PASSWORD_ARGON2ID - As of PHP 7.3 only if compiled with support for it
     *
     * If you choose to use any ARGON algorithm, then you might want to
     * uncomment the "ARGON2i/D Algorithm" options to suit your needs
     */
    public string $hashAlgorithm = PASSWORD_DEFAULT;

    /**
     * --------------------------------------------------------------------
     * ARGON2i/D Algorithm options
     * --------------------------------------------------------------------
     * The ARGON2I method of encryption allows you to define the "memory_cost",
     * the "time_cost" and the number of "threads", whenever a password hash is
     * created.
     * This defaults to a value of 10 which is an acceptable number.
     * However, depending on the security needs of your application
     * and the power of your hardware, you might want to increase the
     * cost. This makes the hashing process takes longer.
     */
    public int $hashMemoryCost = 2048;  // PASSWORD_ARGON2_DEFAULT_MEMORY_COST;

    public int $hashTimeCost = 4;       // PASSWORD_ARGON2_DEFAULT_TIME_COST;
    public int $hashThreads  = 4;        // PASSWORD_ARGON2_DEFAULT_THREADS;

    /**
     * --------------------------------------------------------------------
     * Password Hashing Cost
     * --------------------------------------------------------------------
     * The BCRYPT method of encryption allows you to define the "cost"
     * or number of iterations made, whenever a password hash is created.
     * This defaults to a value of 10 which is an acceptable number.
     * However, depending on the security needs of your application
     * and the power of your hardware, you might want to increase the
     * cost. This makes the hashing process takes longer.
     *
     * Valid range is between 4 - 31.
     */
    public int $hashCost = 10;

    /**
     * ////////////////////////////////////////////////////////////////////
     * OTHER SETTINGS
     * ////////////////////////////////////////////////////////////////////
     */

    /**
     * --------------------------------------------------------------------
     * User Provider
     * --------------------------------------------------------------------
     * The name of the class that handles user persistence.
     * By default, this is the included UserModel, which
     * works with any of the database engines supported by CodeIgniter.
     * You can change it as long as they adhere to the
     * CodeIgniter\Shield\Models\UserModel.
     *
     * @var class-string<UserModel>
     * @phpstan-ignore-next-line
     */
    public string $userProvider = 'App\Models\UserModel';

    /**
     * --------------------------------------------------------------------
     * Phone code Lifetime
     * --------------------------------------------------------------------
     * Specifies the amount of time, in seconds, that a phone code is valid.
     */
    public int $phoneCodeLifetime = 10 * MINUTE;

    /**
     * --------------------------------------------------------------------
     * Phone code repeat time
     * --------------------------------------------------------------------
     * Specifies the period of time (in seconds) after which it will be possible to receive the code again.
     */
    public int $phoneCodeRepeatTime = 1 * MINUTE;

    /**
     * --------------------------------------------------------------------
     * Email verification token Lifetime
     * --------------------------------------------------------------------
     * Specifies the amount of time, in seconds, that an email verification token is valid.
     */
    public int $emailVerifyLifetime = 1 * DAY;

    /**
     * Returns the URL that a user should be redirected
     * to after a successful login.
     */
    public function loginRedirect(): string
    {
        $url = auth()->user()->can('admin.access')
            ? site_url(ADMIN_AREA)
            : setting('Auth.redirects')['login'];

        return $this->getUrl($url);
    }

    /**
     * Returns the URL that a user should be redirected
     * to after they are logged out.
     */
    public function logoutRedirect(): string
    {
        $url = setting('Auth.redirects')['logout'];

        return $this->getUrl($url);
    }

    /**
     * Returns the URL the user should be redirected to
     * after a successful registration.
     */
    public function registerRedirect(): string
    {
        $url = setting('Auth.redirects')['register'];

        return $this->getUrl($url);
    }

    protected function getUrl(string $url): string
    {
        return strpos($url, 'http') === 0
            ? $url
            : rtrim(site_url($url), '/ ');
    }
}
