<?php
namespace App\Core;

use Illuminate\Support\Facades\Route;
use App\Commons\RouteSelection;

class RoutingMiddleware {
    public static function insideAuth()
    {
        $listRoutes = RouteSelection::getListRoutes();

        foreach ($listRoutes as $route) {
            Route::{$route['method']}($route['endPoint'], $route['controllers']);
        }
    }
}