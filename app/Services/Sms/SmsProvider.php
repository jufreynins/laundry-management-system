<?php

namespace App\Services\Sms;

interface SmsProvider
{
    /**
     * Send an SMS message. Returns true if the provider accepted the message.
     */
    public function send(string $toPhoneNumber, string $message): bool;
}
