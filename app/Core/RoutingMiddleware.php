<?php
namespace App\Core;

use Illuminate\Support\Facades\Route;
use App\Commons\CommonEnv;

class RoutingMiddleware {
    public static function generates()
    {
        $listRoutes = CommonEnv::getListRoutes();

        foreach ($listRoutes as $route) {
            Route::{$route['method']}($route['endPoint'], $route['controllers']);
        }
    }
}