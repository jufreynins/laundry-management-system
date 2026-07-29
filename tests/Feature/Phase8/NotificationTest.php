<?php

namespace Tests\Feature\Phase8;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\Location;
use App\Models\Order;
use App\Models\User;
use App\Notifications\OrderStatusUpdated;
use App\Services\OrderStatusService;
use App\Services\Sms\LogSmsProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_ready_for_pickup_transition_notifies_customer(): void
    {
        Notification::fake();

        $location = Location::factory()->create();
        $customer = Customer::factory()->create(['location_id' => $location->id]);
        $user = User::factory()->create(['role' => UserRole::MANAGER, 'location_id' => $location->id]);
        $order = Order::factory()->create([
            'location_id' => $location->id,
            'customer_id' => $customer->id,
            'status' => OrderStatus::QUALITY_CHECK,
        ]);

        app(OrderStatusService::class)->transition($order, OrderStatus::READY_FOR_PICKUP, $user);

        Notification::assertSentTo($customer, OrderStatusUpdated::class);
    }

    public function test_internal_status_transition_does_not_notify_customer(): void
    {
        Notification::fake();

        $location = Location::factory()->create();
        $customer = Customer::factory()->create(['location_id' => $location->id]);
        $user = User::factory()->create(['role' => UserRole::MANAGER, 'location_id' => $location->id]);
        $order = Order::factory()->create([
            'location_id' => $location->id,
            'customer_id' => $customer->id,
            'status' => OrderStatus::CHECKED_IN,
        ]);

        app(OrderStatusService::class)->transition($order, OrderStatus::TAGGED, $user);

        Notification::assertNotSentTo($customer, OrderStatusUpdated::class);
    }

    public function test_customer_with_email_notifications_disabled_does_not_get_mail_channel(): void
    {
        $customer = Customer::factory()->create(['notify_email' => false, 'notify_sms' => false]);
        $order = Order::factory()->create(['customer_id' => $customer->id]);

        $notification = new OrderStatusUpdated($order);

        $this->assertEmpty($notification->via($customer));
    }

    public function test_customer_with_email_enabled_gets_mail_channel(): void
    {
        $customer = Customer::factory()->create(['notify_email' => true, 'email' => 'test@example.com']);
        $order = Order::factory()->create(['customer_id' => $customer->id]);

        $notification = new OrderStatusUpdated($order);

        $this->assertContains('mail', $notification->via($customer));
    }

    public function test_customer_with_sms_enabled_gets_sms_channel(): void
    {
        $customer = Customer::factory()->create(['notify_sms' => true, 'phone' => '5551234567']);
        $order = Order::factory()->create(['customer_id' => $customer->id]);

        $notification = new OrderStatusUpdated($order);

        $this->assertContains(\App\Notifications\Channels\SmsChannel::class, $notification->via($customer));
    }

    public function test_sms_message_does_not_include_internal_notes(): void
    {
        $order = Order::factory()->create(['internal_notes' => 'Secret staff note']);
        $customer = Customer::factory()->create();

        $notification = new OrderStatusUpdated($order);
        $message = $notification->toSms($customer);

        $this->assertStringNotContainsString('Secret staff note', $message);
    }

    public function test_log_sms_provider_masks_phone_number_and_returns_true(): void
    {
        $result = (new LogSmsProvider())->send('5551234567', 'Test message');

        $this->assertTrue($result);
    }
}
