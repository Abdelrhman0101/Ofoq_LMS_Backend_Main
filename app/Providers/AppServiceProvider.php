<?php

namespace App\Providers;

use App\Models\Reviews;
use App\Observers\ReviewObserver;
use App\Models\Course;
use App\Observers\CourseObserver;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;
use App\Models\User;


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
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(1000)->by(optional($request->user())->id ?: $request->ip());
        });

        RateLimiter::for(User::class . '::api', function (Request $request) {
            return Limit::perMinute(1000)->by(optional($request->user())->id ?: $request->ip());
        });

        // Mirror route-specific limiter for final exam meta here to match environments
        RateLimiter::for('final_exam_meta', function (Request $request) {
            return Limit::perMinute(300)->by(optional($request->user())->id ?: $request->ip());
        });
        RateLimiter::for(User::class . '::final_exam_meta', function (Request $request) {
            return Limit::perMinute(300)->by(optional($request->user())->id ?: $request->ip());
        });

        Route::prefix('api/admin')
            ->middleware(['api', 'auth:sanctum', 'role:admin'])
            ->group(base_path('routes/admin.php'));
        Reviews::observe(ReviewObserver::class);
        Course::observe(CourseObserver::class);
    }
}
