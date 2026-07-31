<?php

namespace App\Providers;

use App\Services\OnlinePayment\PaymentProvider;
use App\Services\OnlinePayment\StubPaymentProvider;
use App\Services\Sms\LogSmsProvider;
use App\Services\Sms\SmsProvider;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(SmsProvider::class, LogSmsProvider::class);
        $this->app->bind(PaymentProvider::class, StubPaymentProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::defaultView('vendor.pagination.gentelella');

        if ($this->app->environment('production')) {
            URL::forceScheme('https');

            // Defense in depth: enforce secure session cookies in production
            // regardless of .env configuration drift.
            config([
                'session.secure' => true,
                'session.http_only' => true,
                'session.same_site' => 'lax',
            ]);
        }
    }
}
