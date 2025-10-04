<?php

namespace App\Providers;

use App\Models\Reviews;
use App\Observers\ReviewObserver;
use Illuminate\Support\Facades\Route;
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
        Route::prefix('api/admin')
            ->middleware('api')
            ->group(base_path('routes/admin.php'));
        Reviews::observe(ReviewObserver::class);
    }
}
