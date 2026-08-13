<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class ModuleServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $moduleRoutes = glob(app_path('Modules/*/Routes/api.php')) ?: [];

        foreach ($moduleRoutes as $routes) {
            Route::middleware('api')
                ->prefix('api/v1')
                ->group($routes);
        }
    }
}
