<?php

namespace App\Validation;

/**
 * Validation Rules.
 */
class CommonRules
{
    public function valid_phone(string $str): bool
    {
        helper('common');
        $phoneNumber = \App\Helpers\PhoneHelper::format('', $str, ['type' => 'clear']);
        return $phoneNumber !== null;
    }
}