<?php

namespace App\Models;

use App\Auth\Authentication\Authenticators\Session;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Models\CheckQueryReturnTrait;
use CodeIgniter\Shield\Models\UserIdentityModel as ShieldIdentity;

class UserIdentityModel extends ShieldIdentity
{
    use CheckQueryReturnTrait;

    /**
     * Creates a new identity for this user with a phone/password
     * combination.
     *
     * @phpstan-param array{phone: string, password: string} $credentials
     */
    public function createPhoneIdentity(User $user, array $credentials): void
    {
        $passwords = service('passwords');

        $return = $this->insert([
            'user_id' => $user->id,
            'type'    => Session::ID_TYPE_PHONE,
            'secret'  => $credentials['phone'],
            'secret2' => $passwords->hash($credentials['password']),
        ]);

        $this->checkQueryReturn($return);
    }
}