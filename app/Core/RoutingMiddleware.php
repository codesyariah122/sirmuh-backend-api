<?php
namespace App\Core;

use Illuminate\Support\Facades\Route;
use App\Commons\RouteSelection;

class RoutingMiddleware extends RouteSelection {

	public static function insideAuth()
	{
		$listRoutes = self::getListRoutes();
		foreach ($listRoutes as $route) {
			Route::{$route['method']}($route['endPoint'], $route['controllers']);
		}
	}
}