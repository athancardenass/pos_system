<?php

namespace App\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
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
        View::composer('layouts.app', function ($view): void {
            $employee = auth()->user();
            $employee?->loadMissing('role');

            $navModules = collect(config('roles.primary_navigation', []))
                ->filter(fn (array $roles) => $employee?->hasRole(...$roles))
                ->keys()
                ->all();

            $view->with([
                'navEmployee' => $employee,
                'navModules' => $navModules,
            ]);
        });
    }
}
