<?php

namespace App\Libraries\DaData;

/**
 *  Company.
 */
class Company
{
    public ?string $name;

    public string $inn;

    public ?string $kpp;

    public ?string $okpo;

    public Address $address;

    public function fill(?array $data = null): Company
    {
        foreach ($data as $key => $value) {
            $this->{$key} = $value;
        }
        return $this;
    }

    public function toJSON(): ?string
    {
        return $this->inn ? json_encode([
            'name'    => $this->name,
            'inn'     => $this->inn,
            'kpp'     => $this->kpp,
            'okpo'    => $this->okpo,
            'address' => $this->address->toArray(),
        ]) : null;
    }

}
