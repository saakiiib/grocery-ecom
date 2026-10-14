<?php

namespace App\Providers;

use App\Models\CompanyDetails;
use App\Support\MailSettings;
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
        try {
            MailSettings::apply();
        } catch (\Throwable $e) {
            // Settings table may not exist yet (fresh install / migrate).
        }
        View::composer('*', function ($view) {
            if (! $view->offsetExists('company')) {
                $view->with('company', CompanyDetails::cached());
            }
            if (! $view->offsetExists('siteBrand')) {
                $company = CompanyDetails::cached();
                $siteBrand = trim((string) ($company->company_name ?? ''));
                if ($siteBrand === '' || strcasecmp($siteBrand, 'Evergreen') === 0) {
                    $siteBrand = 'Alam Mini Market';
                }
                $view->with('siteBrand', $siteBrand);
            }
        });
    }
}
