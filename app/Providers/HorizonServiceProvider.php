<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\HorizonApplicationServiceProvider;
use Override;

class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    #[Override]
    public function boot(): void
    {
        parent::boot();
    }

    #[Override]
    protected function authorization()
    {
        $this->gate();

        Horizon::auth(
            static fn($request) => true
        );
    }

    /**
     * Register the Horizon gate.
     *
     * This gate determines who can access Horizon in non-local environments.
     *
     * @return void
     */
    #[Override]
    protected function gate()
    {
        Gate::define(
            'viewHorizon',
            static fn($user) => true
        );
    }
}
