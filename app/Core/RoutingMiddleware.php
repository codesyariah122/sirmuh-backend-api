<?php
namespace App\Core;

use Illuminate\Support\Facades\Route;
use App\Commons\CommonEnv;

class RoutingMiddleware {

	public static function generates()
    {
        $routes = CommonEnv::getListRoutes();

        foreach ($routes as $route) {
            $method = strtolower($route['method']); 
            Route::$method($route['endPoint'], [$route['controllers'][0], $route['controllers'][1]]);
        }
    }
}