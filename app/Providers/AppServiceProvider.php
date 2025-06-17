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
                // app(Schedule::class)->command('app:test-sms-reminder')->daily(); // run daily
                // app(Schedule::class)->command('vaccinations:remind')->daily(); // vaccination reminders daily
                app(Schedule::class)->command('checkups:monthly-reminder')->daily(); // monthly checkup reminders
                app(Schedule::class)->command('appointments:send-notifications')->everyMinute(); // unified appointment reminders daily
                app(Schedule::class)->command('vaccinations:update-missed')->daily(); // Check for missed vaccinations daily
                app(Schedule::class)->command('vitamin-deworming:update-missed')->daily(); // Check for missed vitamin/deworming visits daily
                app(Schedule::class)->command('vitamin-deworming:remind')->daily(); // Send vitamin/deworming reminders daily
                app(Schedule::class)->command('foodfacts:send-notifications')->everyMinute(); // monthly food fact notifications
            });
        }
    }
}
