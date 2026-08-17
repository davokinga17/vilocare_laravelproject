<?php

namespace App\Providers;

use App\Models\NotificationLog;
use App\Services\DashboardSummaryService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton('notification.center', function ($app) {
            return function () use ($app): array {
                $user = auth()->user();
                $visible = (bool) $user?->canManageUsers();

                if (! $visible) {
                    return [
                        'visible' => false,
                        'recent_notifications' => collect(),
                        'upcoming_appointments' => collect(),
                    ];
                }

                $summary = $app->make(DashboardSummaryService::class)->summary();

                return [
                    'visible' => true,
                    'recent_notifications' => Schema::hasTable('notification_logs')
                        ? NotificationLog::query()->latest()->limit(6)->get()
                        : collect(),
                    'upcoming_appointments' => collect($summary['upcomingAppointments'] ?? [])
                        ->take(8)
                        ->values(),
                ];
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        View::composer('layouts.app', function ($view) {
            /** @var callable(): array $resolver */
            $resolver = app('notification.center');
            $view->with('notificationCenter', $resolver());
        });

        RateLimiter::for('dashboard-chatbot', function (Request $request) {
            return Limit::perMinute(8)->by($request->user()?->getKey() ?: $request->ip());
        });

        RateLimiter::for('ai-assistant', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()?->getKey() ?: $request->ip());
        });
    }
}
