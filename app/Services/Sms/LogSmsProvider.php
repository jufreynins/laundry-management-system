<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Log;

/**
 * Default SMS provider for local/dev/MVP use — logs the message instead of
 * sending it. Swap the SmsProvider binding in AppServiceProvider for a real
 * provider (Twilio, etc.) when SMS notifications go live; no calling code
 * needs to change since it only depends on the SmsProvider interface.
 */
class LogSmsProvider implements SmsProvider
{
    public function send(string $toPhoneNumber, string $message): bool
    {
        Log::info('SMS (not actually sent — LogSmsProvider active)', [
            'to' => substr($toPhoneNumber, 0, 3).'****'.substr($toPhoneNumber, -2),
            'message' => $message,
        ]);

        return true;
    }
}
