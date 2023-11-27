<?php

namespace App\Libraries\DaData;

use Dadata\DadataClient as Dadata;

/**
 * Provides DaData service.
 */
class DadataClient extends Dadata
{
    public function __construct()
    {
        parent::__construct(env('dadata.token'), env('dadata.secret'));
    }

    /**
     * Get company data.
     *
     * @param string $query
     * @return object|null
     */
    public function getCompany(string $query): ?Company
    {
        try {
            $companyArr = parent::findById('party', $query, 1);
            $companyData = array_shift($companyArr);

            return (new Company())->fill([
                'name'    => $companyData['value'],
                'inn'     => $companyData['data']['inn'],
                'kpp'     => $companyData['data']['kpp'] ?? null,
                'okpo'    => $companyData['data']['okpo'] ?? null,
                'address' => new Address(
                    $companyData['data']['address']['unrestricted_value'],
                    $companyData['data']['address']['data']
                ),
            ]);

        } catch (\Throwable $e) {
            return null;
        }
    }

    public function getAddress(string $query): ?Address
    {
        try {
            $addressArr = parent::suggest('address', $query, 1);
            $addressData = array_shift($addressArr);

            return (new Address($addressData['unrestricted_value'], $addressData['data']));

        } catch (\Throwable $e) {
            return null;
        }
    }
}