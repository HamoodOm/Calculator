<?php

namespace App\Providers;

use App\Models\ApiClient;
use App\Models\Role;
use App\Policies\ApiClientPolicy;
use App\Policies\RolePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        ApiClient::class => ApiClientPolicy::class,
        Role::class => RolePolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        // Gate to check if a user can create a role at a specific level
        Gate::define('create-role-at-level', function ($user, int $level) {
            return app(RolePolicy::class)->createAtLevel($user, $level);
        });
    }
}
