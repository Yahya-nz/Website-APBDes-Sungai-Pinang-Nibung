<?php

namespace App\Providers;

use App\Models\User;
use App\Support\NotificationFeed;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Gate::define('manage-data', fn (User $user): bool => $user->is_admin);
        RateLimiter::for('login', function (Request $request): Limit {
            $email = $request->input('email');

            return Limit::perMinute(5)->by(Str::lower(is_string($email) ? $email : 'invalid').'|'.$request->ip());
        });
        RateLimiter::for('complaints', fn (Request $request): Limit => Limit::perHour(5)->by($request->ip()));
        RateLimiter::for('votes', fn (Request $request): Limit => Limit::perMinute(20)->by($request->ip()));
        RateLimiter::for('complaint-recovery', fn (Request $request): Limit => Limit::perMinutes(10, 5)->by($request->ip()));
        View::composer('layouts.app', function ($view): void {
            $view->with('notificationFeed', NotificationFeed::for(request()->user()));
        });
    }
}
