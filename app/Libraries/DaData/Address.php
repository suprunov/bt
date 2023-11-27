<?php

namespace App\Libraries\DaData;

class Address
{
    public ?string $name;
    public ?string $postalCode;
    public ?string $country;
    public ?string $regionWithType;
    public ?string $areaWithType;
    public ?string $city;
    public ?string $settlementWithType;
    public ?string $streetWithType;
    public ?string $house;
    public ?string $houseType;
    public ?string $block;
    public ?string $blockType;
    public ?string $flat;
    public ?string $flatType;
    public ?string $kladrId;
    public ?string $geoLat;
    public ?string $geoLon;

    protected array $datamap = [
        'postal_code'          => 'postalCode',
        'country'              => 'country',
        'region_with_type'     => 'regionWithType',
        'area_with_type'       => 'areaWithType',
        'city'                 => 'city',
        'settlement_with_type' => 'settlementWithType',
        'street_with_type'     => 'streetWithType',
        'house'                => 'house',
        'house_type'           => 'houseType',
        'block'                => 'block',
        'block_type'           => 'blockType',
        'flat'                 => 'flat',
        'flat_type'            => 'flatType',
        'kladr_id'             => 'kladrId',
        'geo_lat'              => 'geoLat',
        'geo_lon'              => 'geoLon',
    ];

    public function __construct(?string $name = null, array $data = []) {
        if ($name) {
            $this->name = $name;
            foreach ($this->datamap as $addressType => $addressAlias) {
                $this->{$this->datamap[$addressType]} = $data[$addressType] ?? null;
            }
        } else {
            $this->name = $data['name'];
            foreach ($this->datamap as $addressType => $addressAlias) {
                $this->{$this->datamap[$addressType]} = $data[$addressAlias] ?? null;
            }
        }
    }

    public function toJSON(): ?string
    {
        $data = ['name' => $this->name];
        foreach ($this->datamap as $addressType) {
            $data[$addressType] = $this->{$addressType};
        }
        return json_encode($data);
    }

    public function toArray(): array
    {
        $data = ['name' => $this->name];
        foreach ($this->datamap as $addressType) {
            $data[$addressType] = $this->{$addressType};
        }
        return $data;
    }

}


