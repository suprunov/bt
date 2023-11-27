<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class Contact extends Entity
{
    protected $datamap = [];
    protected $dates = [];
    protected $casts = [];

    public function getPhones(?string $type = null): array
    {
        $phonesData = json_decode($this->attributes['phones']);
        $phones = [];
        if ($phonesData->phone ?? false) {
            foreach ($phonesData->phone as $i => $phone) {
                if (!$type || $type === $phonesData->phone_type[$i])
                    $phones[] = (object)[
                        'code'   => $phonesData->phone_code[$i],
                        'number' => $phone,
                        'tip'    => $phonesData->phone_tip[$i],
                        'type'   => $phonesData->phone_type[$i],
                    ];
            }
        }
        return $phones;
    }

    public function getEmails(): array
    {
        $emailsData = json_decode($this->attributes['emails']);
        $emails = [];
        if ($emailsData->email ?? false) {
            foreach ($emailsData->email as $email) {
                $emails[] = $email;
            }
        }
        return $emails;
    }
}
