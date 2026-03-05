<?php

namespace App\Providers;

use App\Models\Athlete;
use Illuminate\Support\Facades\Gate;
use App\Policies\AthletesPolicy;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{

    protected $policies = [
        \App\Models\Athlete::class => \App\Policies\AthletesPolicy::class, // Map Athlete model to AthletesPolicy
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
        // Register policies manually (in environments without default policy loader)
        Gate::policy(Athlete::class, AthletesPolicy::class);
    }
}
