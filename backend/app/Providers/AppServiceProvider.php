<?php

namespace App\Providers;

use App\Models\Admin;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Fallback for deployments where config/auth.php is outdated.
        if (!config()->has('auth.providers.admins')) {
            config()->set('auth.providers.admins', [
                'driver' => 'eloquent',
                'model' => Admin::class,
            ]);
        }

        if (!config()->has('auth.guards.admin')) {
            config()->set('auth.guards.admin', [
                'driver' => 'jwt',
                'provider' => 'admins',
            ]);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
