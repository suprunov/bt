<?php

namespace App\Auth\Authentication\Authenticators;

//use CodeIgniter\Events\Events;
//use CodeIgniter\HTTP\IncomingRequest;
//use CodeIgniter\HTTP\Response;
use CodeIgniter\Shield\Authentication\Authenticators\Session as ShieldSession;
//use App\Entities\User;
//use CodeIgniter\Shield\Result;

class Session extends ShieldSession
{
    public const ID_TYPE_PHONE_CODE = 'phone-code';
    public const ID_TYPE_PHONE_CODE_CHANGE = 'phone-code-change';
    public const ID_TYPE_PHONE_CODE_CHANGE_PHONE = 'phone-code-phone';
    public const ID_TYPE_PHONE_CODE_CHANGE_EMAIL = 'phone-code-email';
    public const ID_TYPE_EMAIL_VERIFY = 'email-verify';

    /**
     * Override parent function because of "$user->touchIdentity($user->getEmailIdentity());" string
     *
     * Changed:
     *  1) $user->touchIdentity($user->getEmailIdentity()); to $user->touchIdentity($user->getPhoneIdentity());
     *  2) comment $this->setAuthAction();
     *
     * Attempts to authenticate a user with the given $credentials.
     * Logs the user in with a successful check.
     *
     * @phpstan-param array{email?: string, username?: string, password?: string} $credentials
     */
    /*public function attempt(array $credentials): Result
    {
        $request = service('request');

        $ipAddress = $request->getIPAddress();
        $userAgent = (string) $request->getUserAgent();

        $result = $this->check($credentials);

        // Credentials mismatch.
        if (! $result->isOK()) {
            // Always record a login attempt, whether success or not.
            $this->recordLoginAttempt($credentials, false, $ipAddress, $userAgent);

            $this->user = null;

            // Fire an event on failure so devs have the chance to
            // let them know someone attempted to login to their account
            unset($credentials['password']);
            Events::trigger('failedLogin', $credentials);

            return $result;
        }

        $user = $result->extraInfo();

        $this->user = $user;

        // Update the user's last used date on their phone identity.
        //$user->touchIdentity($user->getEmailIdentity());
        $user->touchIdentity($user->getPhoneIdentity());

        // Set auth action from database.
        //$this->setAuthAction();

        // If an action has been defined for login, start it up.
        $this->startUpAction('login', $user);

        $this->startLogin($user);

        $this->recordLoginAttempt($credentials, true, $ipAddress, $userAgent, $user->id);

        $this->issueRememberMeToken();

        if (! $this->hasAction()) {
            $this->completeLogin($user);
        }

        return $result;
    }*/

    /**
     * Override parent function because of it's private for unknown reasons
     *
     * Changed: nothing!
     */
/*    private function issueRememberMeToken(): void
    {
        if ($this->shouldRemember && setting('Auth.sessionConfig')['allowRemembering']) {
            $this->rememberUser($this->user);

            // Reset so it doesn't mess up future calls.
            $this->shouldRemember = false;
        } elseif ($this->getRememberMeToken()) {
            $this->removeRememberCookie();

            // @TODO delete the token record.
        }

        // We'll give a 20% chance to need to do a purge since we
        // don't need to purge THAT often, it's just a maintenance issue.
        // to keep the table from getting out of control.
        if (random_int(1, 100) <= 20) {
            $this->rememberModel->purgeOldRememberTokens();
        }
    }*/

    /**
     * Override parent function because of it's private for unknown reasons
     *
     * Changed: nothing!
     */
/*    private function removeRememberCookie()
    {
        $response = service('response');

        // Remove remember-me cookie
        $response->deleteCookie(
            setting('Auth.sessionConfig')['rememberCookieName'],
            setting('Cookie.domain'),
            setting('Cookie.path'),
            setting('Cookie.prefix')
        );
    }*/

    /**
     * Override parent function because of it's private for unknown reasons
     *
     * Changed: nothing!
     */
/*    private function getRememberMeToken(): ?string
    {
        $request = service('request');

        $cookieName = setting('Cookie.prefix') . setting('Auth.sessionConfig')['rememberCookieName'];

        return $request->getCookie($cookieName);
    }*/

    /**
     * Override parent function because of "$idType = (! isset($credentials['email']) && isset($credentials['username']))" string
     *
     * Changed:
     *  1) "$idType = (! isset($credentials['email']) && isset($credentials['username'])) ? self::ID_TYPE_USERNAME : self::ID_TYPE_EMAIL_PASSWORD;"
     *     to "$idType = self::ID_TYPE_PHONE;"
     *  2) "$credentials['email'] ?? $credentials['username']," to "$credentials['phone'],"
     *
     * @param int|string|null $userId
     */
/*    private function recordLoginAttempt(
        array $credentials,
        bool $success,
        string $ipAddress,
        string $userAgent,
        $userId = null
    ): void {
        // $idType = (! isset($credentials['email']) && isset($credentials['username']))
        //   ? self::ID_TYPE_USERNAME
        //    : self::ID_TYPE_EMAIL_PASSWORD;
        $idType = self::ID_TYPE_PHONE;

        $this->loginModel->recordLoginAttempt(
            $idType,
            // $credentials['email'] ?? $credentials['username'],
            $credentials['phone'],
            $success,
            $ipAddress,
            $userAgent,
            $userId
        );
    }*/

    /**
     * Updates the user's last active date and last location.
     */
    /*public function recordActiveDate(): void
    {
        // Update the user's last location
        //$this->user->last_location_id = session()->get('location_id');

        // Update the user's last active date
        parent::recordActiveDate();
    }*/
}
