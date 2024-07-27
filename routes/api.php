<?php
/**
 * @author: Puji Ermanto
 * Build from scratch
 * */

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\{LoginController, PublicFeatureController};
use App\Core\RoutingMiddleware;
use App\Events\TestingEvent;

Route::middleware(['auth:api', 'cors', 'json.response', 'session.expired'])
->prefix('v1')
->group(function () {
    RoutingMiddleware::insideAuth();
});

Route::middleware('cors')
->prefix('v1')
->group(function () {
    Route::post('/login', [LoginController::class, 'login']);
    Route::post('/force-logout', [LoginController::class, 'force_logout']);
    Route::get('/detail', [PublicFeatureController::class, 'detail_data']);
    Route::get('/data-toko', [PublicFeatureController::class, 'data_toko']);
});

Route::get('/testing-event', function () {
    broadcast(new TestingEvent('Testing maanggsss ah'));
});
