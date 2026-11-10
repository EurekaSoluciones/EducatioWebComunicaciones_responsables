<?php

namespace App\Providers;

use Illuminate\Http\Request;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Support\Carbon;
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
     * Bootstrap any application services.>
     */
    public function boot(): void
    {
        Carbon::setlocale(config('app.locale'));
        AuthenticateSession::redirectUsing(fn (Request $request) => $request->expectsJson() ? null : route('login'));
        // The remaining web Blade pages share the favicon with React. Mobile
        // appshow templates are standalone and do not use this layout.
        View::composer('adminlte::master', function (\Illuminate\View\View $view) {
            $factory = $view->getFactory();
            $factory->startSection('meta_tags');
            echo view('partials.favicon')->render();
            $factory->appendSection();
        });

    }
}
