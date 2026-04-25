<?php

namespace App\Providers;

use App\Domains\Notification\Contracts\PushNotification;
use App\Infrastructure\Firebase\FcmV1HttpClient;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class NotificationServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(PushNotification::class, FcmV1HttpClient::class);
    }

    public function provides(): array
    {
        return [PushNotification::class];
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
