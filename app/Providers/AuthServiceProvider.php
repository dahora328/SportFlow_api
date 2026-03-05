<?php

namespace App\Providers;

use App\Models\Athlete;
use App\Policies\AthletesPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        Athlete::class => AthletesPolicy::class,
    ];

    /**
     * Register any authentication/authorization services.
     */
    public function boot()
    {
        $this->registerPolicies();
    }
}
