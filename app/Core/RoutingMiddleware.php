<?php
/**
 * @author: Puji Ermanto
 * Build from scratch
 * */
namespace App\Core;

use Illuminate\Support\Facades\Route;
use App\Commons\RouteSelection;

class RoutingMiddleware extends RouteSelection {

	public static function insideAuth()
	{
		$listRoutes = self::getListRoutes();
		foreach ($listRoutes as $route) {
			if ($route['method'] === 'resource') {
				Route::resource($route['endPoint'], $route['controllers']);
			} else {
				Route::{$route['method']}($route['endPoint'], $route['controllers']);
			}
		}
	}
}