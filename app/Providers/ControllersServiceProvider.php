<?php
namespace App\Providers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class ControllersServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $controllers = config('controllers');

        foreach ($controllers as $controller) {
            var_dump($controller); die;
            $controllerName = class_basename($controller);

            $this->app->make('App\Http\Controllers\\' . $controllerName);

            $namespace = str_replace_last('\\', '/', str_replace('App\\Http\\Controllers\\', '', $controller));

            Route::middleware('auth:api')->prefix('v1')->group(function () use ($controllerName, $namespace) {
                Route::resource($controllerName, 'App\Http\Controllers\\' . $controllerName);
            });
        }
    }
}

