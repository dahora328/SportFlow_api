<?php

namespace App\Providers;

use App\Models\Athletes;
use App\Policies\AthletesPolicy;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{

    protected $policies = [
        Athletes::class => AthletesPolicy::class, // Mapeia o modelo Athletes para a AthletesPolicy
    ];
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
