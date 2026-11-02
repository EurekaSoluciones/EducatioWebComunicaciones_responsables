<?php

namespace App\Providers;

use Illuminate\Http\Request;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Support\Carbon;
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
     * Bootstrap any application services.>
     */
    public function boot(): void
    {
        Carbon::setlocale(config('app.locale'));
        AuthenticateSession::redirectUsing(fn (Request $request) => $request->expectsJson() ? null : route('login'));
        //

    }
}
