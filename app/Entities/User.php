<?php

namespace App\Entities;

use App\Libraries\DaData\Address;
use App\Libraries\DaData\Company;
use Bonfire\Users\User as BonfireUser;
use CodeIgniter\Database\Exceptions\DataException;
use App\Auth\Authentication\Authenticators\Session;
use CodeIgniter\Shield\Entities\UserIdentity;
use App\Models\UserIdentityModel;

class User extends BonfireUser
{
    //private ?string $phone      = null;
    //private ?string $phone_code = null;

    /**
     * If $phone has been updated, the user's phone id entry will be updated with the correct value.
     */
/*    public function savePhoneIdentity1(): bool
    {
        if (empty($this->phone)) {
            return true;
        }

        $identity = $this->getPhoneIdentity();
        if ($identity === null) {
            // Ensure we reload all identities
            //$this->identities = null;

            $this->createPhoneIdentity([
                'phone'    => $this->phone,
                'password' => $this->phone_code ?? '',
            ]);
            $identity = $this->getPhoneIdentity();
        }

        if (! empty($this->phone)) {
            $identity->secret = $this->phone;
        }

        if (! empty($this->phone_code)) {
            $identity->secret2 = service('passwords')->hash($this->phone_code);
        }

        $identityModel = model(UserIdentityModel::class);

        try {
            $identityModel->save($identity);
        } catch (DataException $e) {
            // There may be no data to update.
            $messages = [
                lang('Database.emptyDataset', ['insert']),
                lang('Database.emptyDataset', ['update']),
            ];
            if (in_array($e->getMessage(), $messages, true)) {
                return true;
            }

            throw $e;
        }

        return true;
    }*/

    /**
     * Returns the user's Phone identity.
     */
    /*public function getPhoneIdentity1(): ?UserIdentity
    {
        $identityModel = model(UserIdentityModel::class);

        return $identityModel->getIdentityByType($this, Session::ID_TYPE_PHONE);
    }*/

    /**
     * Creates a new identity for this user with a phone/password
     * combination.
     *
     * @phpstan-param array{email: string, password: string} $credentials
     */
    public function createPhoneIdentity1(array $credentials): void
    {
        /** @var UserIdentityModel $identityModel */
        $identityModel = model(UserIdentityModel::class);

        $identityModel->createPhoneIdentity($this, $credentials);
    }


/*    public function setPhone1(int $phone): void
    {
        $this->phone = $phone;
    }*/

    /*public function getPhone1(): ?int
    {
        return $this->phone;
    }*/

/*    public function setPhoneCode1(string $phoneCode): void
    {
        $this->phone_code = $phoneCode;
    }*/

    /*public function getPhoneCode1(): ?string
    {
        return $this->phone_code;
    }*/


    public function getLegalData(): ?Company
    {
        $legalData = $this->attributes['legal_data'] ?
            (json_decode($this->attributes['legal_data']) ?? null) : null;
        return (new Company())->fill([
            'name'    => $this->attributes['name'],
            'inn'     => $this->attributes['inn'],
            'kpp'     => $legalData->kpp ?? null,
            'okpo'    => $legalData->okpo ?? null,
            'address' => new Address(null, (array)$legalData->address ?? []),
        ]);
    }

}
