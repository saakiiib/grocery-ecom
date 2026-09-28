<?php

namespace App\Providers;

use App\Models\CompanyDetails;
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
        View::composer('*', function ($view) {
            if (! $view->offsetExists('company')) {
                $view->with('company', CompanyDetails::cached());
            }
        });
    }
}
