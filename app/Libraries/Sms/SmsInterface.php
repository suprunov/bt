<?php

namespace App\Libraries\Sms;

/**
 * Sms sending interface
 */
interface SmsInterface
{
    /**
     * Send single sms to phone number
     *
     * @param int $phone
     * @param string $message
     *
     * @return bool
     */
    public function send(int $phone, string $message) : bool;
}
