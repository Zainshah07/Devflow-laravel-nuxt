<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Policies\ProjectPolicy;
use App\Models\Project;
use Illuminate\Support\Facades\Gate;
use App\Services\TokenService;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
           // Bind TokenService as a singleton so the same instance
        // is reused across the request lifecycle
        $this->app->singleton(TokenService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Project::class, ProjectPolicy::class);
    }
}
