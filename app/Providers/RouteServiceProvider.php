<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use App\Models\User;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        parent::boot();

        // Add custom route binding for course parameter
        Route::bind('course', function ($value) {
            // If the current route is an admin route, bypass global scopes
            if (request()->is('api/admin/*')) {
                return \App\Models\Course::withoutGlobalScopes()->findOrFail($value);
            }

            // Otherwise, use the default binding (which includes global scopes)
            return \App\Models\Course::findOrFail($value);
        });

        $this->configureRateLimiting();

        $this->routes(function () {
            // مسارات الواجهة البرمجية
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            // مسارات الويب
            Route::middleware('web')
                ->group(base_path('routes/web.php'));

            // مسارات الإدارة (اختياري)
            Route::middleware(['api', 'auth:sanctum', 'role:admin'])
                ->prefix('api/admin')
                ->group(base_path('routes/admin.php'));
        });
    }

    /**
     * تعريف محددات الـ Rate Limiter
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by(optional($request->user())->id ?: $request->ip());
        });
        // تعريف المحدِّد المطبَّع (Typed) لواجهة "api" ليطابق رسالة الخطأ "App\\Models\\User::api"
        RateLimiter::for(User::class.'::api', function (Request $request) {
            return Limit::perMinute(60)->by(optional($request->user())->id ?: $request->ip());
        });
        // Route-specific rate limiter for final exam meta to allow more frequent checks
        RateLimiter::for('final_exam_meta', function (Request $request) {
            return Limit::perMinute(300)->by(optional($request->user())->id ?: $request->ip());
        });
    }
}

