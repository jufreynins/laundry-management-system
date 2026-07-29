<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderStatusUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Order $order)
    {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = [];

        if ($notifiable->notify_email && $notifiable->email) {
            $channels[] = 'mail';
        }

        if ($notifiable->notify_sms && $notifiable->phone) {
            $channels[] = \App\Notifications\Channels\SmsChannel::class;
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $trackingUrl = route('public.tracking.show', $this->order->tracking_token);

        return (new MailMessage)
            ->subject("Update on your order {$this->order->order_number} — {$this->order->location->name}")
            ->line("Your order {$this->order->order_number} status is now: {$this->order->status->customerLabel()}.")
            ->when($this->order->promised_at, fn ($mail) => $mail->line('Promised: '.$this->order->promised_at->format('m/d/Y g:i A')))
            ->action('Track Your Order', $trackingUrl)
            ->line('Thank you for your business.');
    }

    public function toSms(object $notifiable): string
    {
        $trackingUrl = route('public.tracking.show', $this->order->tracking_token);

        return "Order {$this->order->order_number}: {$this->order->status->customerLabel()}. Track: {$trackingUrl}";
    }
}
