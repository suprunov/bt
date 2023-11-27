<?php

namespace App\Entities\Traits;

/**
 * Provides "special fields" support to Entities.
 */
trait SpecialFields
{
    public function setCode(string $string)
    {
        helper('common');

        $this->attributes['code'] = \App\Helpers\StringHelper::makeCode($string);

        return $this;
    }
}
