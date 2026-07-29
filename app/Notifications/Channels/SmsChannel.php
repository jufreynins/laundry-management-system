<?php

namespace App\Notifications\Channels;

use App\Services\Sms\SmsProvider;
use Illuminate\Notifications\Notification;

class SmsChannel
{
    public function __construct(private readonly SmsProvider $provider)
    {
    }

    public function send(mixed $notifiable, Notification $notification): void
    {
        if (!method_exists($notification, 'toSms')) {
            return;
        }

        $phone = $notifiable->routeNotificationFor('sms', $notification);
        if (empty($phone)) {
            return;
        }

        $this->provider->send($phone, $notification->toSms($notifiable));
    }
}
