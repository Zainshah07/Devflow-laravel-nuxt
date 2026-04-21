<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Policies\ProjectPolicy;
use App\Models\Project;
use Illuminate\Support\Facades\Gate;
use App\Services\TokenService;
use App\Observers\TaskObserver;
use App\Models\Task;
use App\Services\TaskCacheService;
use App\Services\TaskDependencyService;
use Laravel\Sanctum\Sanctum;
use App\Models\PersonalAccessToken;



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
        $this->app->singleton(TaskCacheService::class);
        $this->app->singleton(TaskDependencyService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Project::class, ProjectPolicy::class);
        Task::observe(TaskObserver::class);
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);
    }
}
