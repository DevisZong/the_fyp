<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\App;
use Illuminate\Console\Scheduling\Schedule;

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
        if (App::runningInConsole()) {
            $this->app->booted(function () {
                // app(Schedule::class)->command('app:test-sms-reminder')->everyMinute(); // run every minute
                app(Schedule::class)->command('vaccinations:remind')->everyMinute(); // vaccination reminders daily
                app(Schedule::class)->command('checkups:monthly-reminder')->everyMinute(); // monthly checkup reminders
            });
        }
    }
}

