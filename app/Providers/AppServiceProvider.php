<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\GatewayInterface;
use App\Contracts\NotificationRepositoryInterface;
use App\Gateways\EmailGateway;
use App\Gateways\SmsGateway;
use App\Repositories\NotificationRepository;
use Illuminate\Support\ServiceProvider;
use Override;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    #[Override]
    public function register(): void
    {
        $this->app->bind(fn($app): GatewayInterface => match (request()->input('channel')) {
            'email' => $app->make(EmailGateway::class),
            default => $app->make(SmsGateway::class),
        });

        $this->app->bind(NotificationRepositoryInterface::class, NotificationRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
